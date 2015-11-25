<?php

namespace Sineflow\ElasticsearchBundle\Manager;

use Sineflow\ElasticsearchBundle\Document\DocumentInterface;
use Sineflow\ElasticsearchBundle\Document\Provider\ProviderInterface;
use Sineflow\ElasticsearchBundle\Document\Provider\ProviderRegistry;
use Sineflow\ElasticsearchBundle\Document\Repository\Repository;
use Sineflow\ElasticsearchBundle\Document\Repository\RepositoryInterface;
use Sineflow\ElasticsearchBundle\Exception\BulkRequestException;
use Sineflow\ElasticsearchBundle\Exception\Exception;
use Sineflow\ElasticsearchBundle\Exception\IndexRebuildingException;
use Sineflow\ElasticsearchBundle\Exception\NoReadAliasException;
use Sineflow\ElasticsearchBundle\Finder\Finder;
use Sineflow\ElasticsearchBundle\Mapping\DocumentMetadata;
use Sineflow\ElasticsearchBundle\Mapping\DocumentMetadataCollector;
use Sineflow\ElasticsearchBundle\Result\DocumentConverter;

/**
 * Manager class.
 */
class IndexManager
{
    /**
     * @var string The unique manager name (the key from the index configuration)
     */
    private $managerName;

    /**
     * @var ConnectionManager Elasticsearch connection.
     */
    private $connection;

    /**
     * @var DocumentMetadataCollector
     */
    private $metadataCollector;

    /**
     * @var ProviderRegistry
     */
    private $providerRegistry;

    /**
     * @var Finder
     */
    private $finder;

    /**
     * @var DocumentConverter
     */
    private $documentConverter;

    /**
     * @var array
     */
    private $indexSettings;

    /**
     * @var RepositoryInterface[]
     */
    private $repositories = [];

    /**
     * @var bool Whether to use index aliases
     */
    private $useAliases = true;

    /**
     * @var string The alias where data should be read from
     */
    private $readAlias = null;

    /**
     * @var string The alias where data should be written to
     */
    private $writeAlias = null;

    /**
     * @var string The separator string between property names and language codes for ML properties
     */
    private $languageSeparator;

    /**
     * @param string                    $managerName
     * @param ConnectionManager         $connection
     * @param DocumentMetadataCollector $metadataCollector
     * @param ProviderRegistry          $providerRegistry
     * @param Finder                    $finder
     * @param DocumentConverter         $documentConverter
     * @param array                     $indexSettings
     * @param string                    $languageSeparator
     */
    public function __construct(
        $managerName,
        ConnectionManager $connection,
        DocumentMetadataCollector $metadataCollector,
        ProviderRegistry $providerRegistry,
        Finder $finder,
        DocumentConverter $documentConverter,
        array $indexSettings,
        $languageSeparator)
    {
        $this->managerName = $managerName;
        $this->connection = $connection;
        $this->metadataCollector = $metadataCollector;
        $this->providerRegistry = $providerRegistry;
        $this->finder = $finder;
        $this->documentConverter = $documentConverter;
        $this->indexSettings = $indexSettings;
        $this->languageSeparator = $languageSeparator;

        if (true === $this->getUseAliases()) {
            $this->readAlias = $indexSettings['index'];
            $this->writeAlias = $indexSettings['index'] . '_write';
        }
    }

    /**
     * @return string
     */
    public function getManagerName()
    {
        return $this->managerName;
    }

    /**
     * @param bool $useAliases
     */
    public function setUseAliases($useAliases)
    {
        $this->useAliases = $useAliases;
    }

    /**
     * @return bool
     */
    public function getUseAliases()
    {
        return $this->useAliases;
    }

    /**
     * @return string
     */
    public function getReadAlias()
    {
        return $this->readAlias;
    }

    /**
     * @return string
     */
    public function getWriteAlias()
    {
        return $this->writeAlias;
    }

    /**
     * @param string $writeAlias
     */
    private function setWriteAlias($writeAlias)
    {
        $this->writeAlias = $writeAlias;
    }

    /**
     * Returns Elasticsearch connection.
     *
     * @return ConnectionManager
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Returns repository for a document class
     *
     * @param string $documentClass
     *
     * @return Repository
     */
    public function getRepository($documentClass)
    {
        if (isset($this->repositories[$documentClass])) {
            return $this->repositories[$documentClass];
        }

        $repositoryClass = $this->metadataCollector->getDocumentMetadata($documentClass)->getRepositoryClass() ?: Repository::class;
        $repo = new $repositoryClass($this, $documentClass, $this->finder);

        if (!($repo instanceof RepositoryInterface)) {
            throw new \InvalidArgumentException(sprintf('Repository "%s" must implement "%s"', $repositoryClass, RepositoryInterface::class));
        }
        $this->repositories[$documentClass] = $repo;

        return $repo;
    }

    /**
     * Returns the data provider object for a type (provided in short class notation, e.g AppBundle:Product)
     *
     * @param string $documentClass The document class for the type
     * @return ProviderInterface
     */
    public function getDataProvider($documentClass)
    {
        $provider = $this->providerRegistry->getProviderInstance($documentClass);

        return $provider;
    }

    /**
     * Returns the base index name this manager is attached to.
     *
     * When using aliases, this would not represent an actual physical index.
     * getReadAlias() and getWriteAlias() should be used instead
     *
     * @return string
     */
    public function getBaseIndexName()
    {
        return $this->indexSettings['index'];
    }

    /**
     * Return a name for a new index, which does not already exist
     */
    private function getUniqueIndexName()
    {
        $indexName = $baseName = $this->getBaseIndexName() . '_' . date('YmdHis');

        $i = 1;
        // Keep trying other names until there is no such existing index or alias
        while ($this->getConnection()->existsIndexOrAlias(array('index' => $indexName))) {
            $indexName = $baseName . '_' . $i;
            $i++;
        }

        return $indexName;
    }

    /**
     * Creates elasticsearch index and adds aliases to it depending on index settings
     *
     * @throws Exception
     */
    public function createIndex()
    {
        $settings = $this->indexSettings;

        if (true === $this->getUseAliases()) {
            // Make sure the read and write aliases do not exist already as aliases or physical indices
            if ($this->getConnection()->existsIndexOrAlias(array('index' => $this->readAlias))) {
                throw new Exception(sprintf('Read alias "%s" already exists as an alias or an index', $this->readAlias));
            }
            if ($this->getConnection()->existsIndexOrAlias(array('index' => $this->writeAlias))) {
                throw new Exception(sprintf('Write alias "%s" already exists as an alias or an index', $this->writeAlias));
            }

            // Create physical index with a unique name
            $settings['index'] = $this->getUniqueIndexName();
            $this->getConnection()->getClient()->indices()->create($settings);

            // Set aliases to index
            $setAliasParams = [
                'body' => [
                    'actions' => [
                        [
                            'add' => [
                                'index' => $settings['index'],
                                'alias' => $this->readAlias
                            ],
                        ],
                        [
                            'add' => [
                                'index' => $settings['index'],
                                'alias' => $this->writeAlias
                            ],
                        ]
                    ],
                ],
            ];
            $this->getConnection()->getClient()->indices()->updateAliases($setAliasParams);

        } else {
            // Make sure the index name does not exist already as a physical index or alias
            if ($this->getConnection()->existsIndexOrAlias(array('index' => $this->getBaseIndexName()))) {
                throw new Exception(sprintf('Index "%s" already exists as an alias or an index', $this->getBaseIndexName()));
            }
            $this->getConnection()->getClient()->indices()->create($settings);
        }
    }

    /**
     * Drops elasticsearch index(es).
     */
    public function dropIndex()
    {
        if (true === $this->getUseAliases()) {
            // Delete all physical indices aliased by the read and write aliases
            $aliasNames = $this->readAlias.','.$this->writeAlias;
            $indices = $this->getConnection()->getClient()->indices()->getAlias(array('name' => $aliasNames));
            $this->getConnection()->getClient()->indices()->delete(['index' => implode(',', array_keys($indices))]);
        } else {
            $this->getConnection()->getClient()->indices()->delete(['index' => $this->getBaseIndexName()]);
        }
    }

    /**
     * Returns the live physical index name
     *
     * @return string
     * @throws Exception If live index is not found
     */
    public function getLiveIndex()
    {
        $indexName = null;

        // Get indices namespace of ES client
        $indices = $this->getConnection()->getClient()->indices();

        if (true === $this->getUseAliases()) {
            if ($this->getConnection()->existsAlias(['name' => $this->readAlias])) {
                $indexName = key($indices->getAlias(['name' => $this->readAlias]));
            }
        } else {
            $indexName = $this->getBaseIndexName();
        }

        if (!$indexName || !$this->getConnection()->existsIndexOrAlias(['index' => $indexName])) {
            throw new Exception('Live index not found');
        }

        return $indexName;
    }

    /**
     * @param bool|true $exceptionIfRebuilding
     * @throws NoReadAliasException     When read alias does not exist
     * @throws IndexRebuildingException When the index is rebuilding, according to the current aliases
     * @throws Exception                When any other problem with the index or aliases mappings exists
     */
    public function verifyIndexAndAliasesState($exceptionIfRebuilding = true)
    {
        if (false === $this->getUseAliases()) {
            // Check that the index exists
            if (!$this->getConnection()->existsIndexOrAlias(['index' => $this->getBaseIndexName()])) {
                throw new Exception(sprintf('Index "%s" does not exist', $this->getBaseIndexName()));
            }
        } else {
            $aliases = $this->getConnection()->getAliases();

            // Check that read alias exists
            if (!isset($aliases[$this->readAlias])) {
                throw new NoReadAliasException(sprintf('Read alias "%s" does not exist', $this->readAlias));
            }
            $liveIndex = key($aliases[$this->readAlias]);

            // Check that read alias points to exactly 1 index
            if (count($aliases[$this->readAlias]) > 1) {
                throw new Exception(sprintf('Read alias "%s" points to more than one index (%s)', $this->readAlias, implode(', ', $aliases[$this->readAlias])));
            }

            // Check that write alias exists
            if (!isset($aliases[$this->writeAlias])) {
                throw new Exception(sprintf('Write alias "%s" does not exist', $this->writeAlias));
            }

            // Check that write alias points to the same index as the read alias
            if (!isset($aliases[$this->writeAlias][$liveIndex])) {
                throw new Exception(sprintf('Write alias "%s" does not point to the live index "%s"', $this->writeAlias, $liveIndex));
            }

            // Check if write alias points to more than one index
            if ($exceptionIfRebuilding && count($aliases[$this->writeAlias]) > 1) {
                $writeAliasIndices = $aliases[$this->writeAlias];
                unset($writeAliasIndices[$liveIndex]);
                throw new IndexRebuildingException(sprintf('Index is currently being rebuilt as "%s"', implode(', ', array_keys($writeAliasIndices))));
            }
        }
    }

    /**
     * Rebuilds ES Index and deletes the old one,
     *
     * @param bool $deleteOld If set, the old index will be deleted upon successful rebuilding
     * @throws Exception
     */
    public function rebuildIndex($deleteOld = false)
    {
        $batchSize = $this->connection->getConnectionSettings()['bulk_batch_size'];

        try {
            if (false === $this->getUseAliases()) {
                throw new Exception('Index rebuilding is not supported, unless you use aliases');
            }

            try {
                // Make sure the index and both aliases are properly set
                $this->verifyIndexAndAliasesState();
            } catch (NoReadAliasException $e) {
                // Looks like the index doesn't exist, so try to create an empty one
                $this->createIndex();
                // Now again make sure that everything is setup correctly
                $this->verifyIndexAndAliasesState();
            }

            // Create a new index
            $settings = $this->indexSettings;
            $oldIndex = $this->getLiveIndex();
            $newIndex = $this->getUniqueIndexName();
            $settings['index'] = $newIndex;
            $this->getConnection()->getClient()->indices()->create($settings);

            // Point write alias to the new index as well
            $setAliasParams = [
                'body' => [
                    'actions' => [
                        [
                            'add' => [
                                'index' => $newIndex,
                                'alias' => $this->writeAlias
                            ],
                        ]
                    ],
                ],
            ];
            $this->getConnection()->getClient()->indices()->updateAliases($setAliasParams);

            // Temporarily override the write alias with the new physical index name, so rebuilding only happens in the new index
            $originalWriteAlias = $this->writeAlias;
            $this->setWriteAlias($settings['index']);

            // Get and cycle all types for the index
            $indexDocumentsMetadata = $this->metadataCollector->getDocumentsMetadataForIndex($this->managerName);
            $documentClasses = array_keys($indexDocumentsMetadata);

            foreach ($documentClasses as $documentClass) {
                $typeDataProvider = $this->getDataProvider($documentClass);
                $i = 1;
                foreach ($typeDataProvider->getDocuments() as $document) {
                    if (is_array($document)) {
                        $documentMetadata = $indexDocumentsMetadata[$documentClass];
                        $this->persistRaw($documentMetadata->getType(), $document);
                    } else {
                        $this->persist($document);
                    }
                    // Send the bulk request every X documents, so it doesn't get too big
                    if ($i % $batchSize == 0) {
                        $this->commit();
                    }
                    $i++;
                }
            }
            // Save any remaining documents to ES
            $this->commit();

            // Restore write alias name
            $this->setWriteAlias($originalWriteAlias);

            // Point both aliases to the new index and remove them from the old
            $setAliasParams = [
                'body' => [
                    'actions' => [
                        [
                            'add' => [
                                'index' => $newIndex,
                                'alias' => $this->readAlias
                            ],
                        ],
                        [
                            'remove' => [
                                'index' => $oldIndex,
                                'alias' => $this->readAlias
                            ],
                        ],
                        [
                            'remove' => [
                                'index' => $oldIndex,
                                'alias' => $this->writeAlias
                            ],
                        ]
                    ],
                ],
            ];
            $this->getConnection()->getClient()->indices()->updateAliases($setAliasParams);

            // Delete the old index
            if ($deleteOld) {
                $this->getConnection()->getClient()->indices()->delete(['index' => $oldIndex]);
                $this->getConnection()->getLogger()->notice(sprintf('Deleted old index %s', $oldIndex));
            }

        } catch (Exception $e) {
            // Bulk exceptions are logged in the connection manager, so only log other exceptions here
            if (!($e instanceof BulkRequestException)) {
                $this->getConnection()->getLogger()->error($e->getMessage());
            }

            // Try to delete the new incomplete index
            if (isset($newIndex)) {
                $this->getConnection()->getClient()->indices()->delete(['index' => $newIndex]);
                $this->getConnection()->getLogger()->notice(sprintf('Deleted incomplete index "%s"', $newIndex));
            }

            // Rethrow exception to be further handled
            throw $e;
        }
    }

    /**
     * Reindex a single document in the ES index
     *
     * @param string $documentClass The document class in short notation (i.e. AppBundle:Product)
     * @param int    $id
     */
    public function reindex($documentClass, $id)
    {
        $documentMetadata = $this->metadataCollector->getDocumentMetadata($documentClass);

        $dataProvider = $this->getDataProvider($documentClass);
        $document = $dataProvider->getDocument($id);

        switch (true) {
            case $document instanceof DocumentInterface:
                if (get_class($document) !== $documentMetadata->getClassName()) {
                    throw new \RuntimeException(sprintf('Document must be "%s", but "%s" was returned from data provider', $documentMetadata->getClassName(), get_class($document)));
                }
                $this->persist($document);
                break;

            case is_array($document):
                if (!isset($document['_id'])) {
                    throw new \RuntimeException(sprintf('The returned document array must include an "_id" field: (%s)', serialize($document)));
                }
                if ($document['_id'] !== $id) {
                    throw new \RuntimeException(sprintf('The document id must be "%s", but "%s" was returned from data provider', $id, $document['_id']));
                }
                $this->persistRaw($documentMetadata->getType(), $document);
                break;

            default:
                throw new \RuntimeException('Document must be either a DocumentInterface instance or an array with raw data');
        }

        $this->commit();
    }

    /**
     * Adds document to a bulk request for the next flush.
     *
     * @param DocumentInterface $document The document entity to index in ES
     */
    public function persist(DocumentInterface $document)
    {
        $documentMetadata = $this->metadataCollector->getDocumentMetadata(get_class($document));
        $documentArray = $this->documentConverter->convertToArray($document);
        $this->persistRaw($documentMetadata->getType(), $documentArray);
    }

    /**
     * Adds a prepared document array to a bulk request for the next flush.
     *
     * @param string $type          Elasticsearch type name
     * @param array  $documentArray The document to index in ES
     */
    public function persistRaw($type, array $documentArray)
    {
        $this->getConnection()->addBulkOperation(
            'index',
            $this->writeAlias,
            $type,
            $documentArray
        );
    }

    /**
     * Commits bulk batch to elasticsearch index.
     */
    public function commit()
    {
        $this->getConnection()->commit();
    }

    /**
     * Return the metadata for documents for this index,
     * optionally filtered by specific document classes in short notation (e.g. AppBundle:Product)
     *
     * @param array $documentClasses If given, only metadata for those classes will be returned
     * @return DocumentMetadata[]
     */
    public function getDocumentsMetadata(array $documentClasses = [])
    {
        $metadata = $this->metadataCollector->getDocumentsMetadataForIndex($this->managerName);

        if (!empty($documentClasses)) {
            return array_intersect_key($metadata, array_flip($documentClasses));
        }

        return $metadata;
    }
}
