<?php

namespace Sineflow\ElasticsearchBundle\ORM;

use Sineflow\ElasticsearchBundle\Client\Connection;
use Sineflow\ElasticsearchBundle\Document\DocumentInterface;
use ONGR\ElasticsearchBundle\Event\ElasticsearchCommitEvent;
use ONGR\ElasticsearchBundle\Event\ElasticsearchPersistEvent;
use ONGR\ElasticsearchBundle\Event\Events;
use Sineflow\ElasticsearchBundle\Mapping\ClassMetadata;
use Sineflow\ElasticsearchBundle\Mapping\ClassMetadataCollection;
use ONGR\ElasticsearchBundle\Result\Converter;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Manager class.
 */
class IndexManager
{
    /**
     * @var Connection Elasticsearch connection.
     */
    private $connection;

    /**
     * @var ClassMetadataCollection
     */
    private $metadataCollection;

    /**
     * @var array
     */
    private $indexSettings;

    /**
     * @var Converter
     */
    private $converter;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

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
     * @param Connection              $connection
     * @param ClassMetadataCollection $metadataCollection
     * @param array                   $indexSettings
     */
    public function __construct(Connection $connection, ClassMetadataCollection $metadataCollection, array $indexSettings)
    {
        $this->connection = $connection;
        $this->metadataCollection = $metadataCollection;
        $this->indexSettings = $indexSettings;

        if (true === $this->getUseAliases()) {
            $this->readAlias = $indexSettings['index'];
            $this->writeAlias = $indexSettings['index'] . '_write';
        }
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
    public function setWriteAlias($writeAlias)
    {
        $this->writeAlias = $writeAlias;
    }

    /**
     * Returns Elasticsearch connection.
     *
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param EventDispatcher $eventDispatcher
     */
    public function setEventDispatcher($eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Returns repository with one or several active selected types.
     *
     * @param string|string[] $type
     *
     * @return Repository
     */
    public function getRepository($type)
    {
        $type = is_array($type) ? $type : [$type];

        foreach ($type as &$selectedType) {
            $this->checkRepositoryType($selectedType);
        }

        return $this->createRepository($type);
    }

    /**
     * Creates a repository.
     *
     * @param array $types
     *
     * @return Repository
     */
    private function createRepository(array $types)
    {
        return new Repository($this, $types);
    }

    /**
     * TODO: Checks if index is already created.
     *
     * @return bool
     */
    public function indexExists()
    {
        return $this->getConnection()->getClient()->indices()->exists(['index' => $this->getIndexName()]);
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
     * Creates fresh elasticsearch index.
     *
     * @param bool $putWarmers Determines if warmers should be loaded.
     * @param bool $noMapping  Determines if mapping should be included.
     */
    public function createIndex($putWarmers = false, $noMapping = false)
    {
        $settings = $this->indexSettings;

        if ($noMapping) {
            unset($settings['body']['mappings']);
        }

        if (true === $this->getUseAliases()) {
            // Make sure the read and write aliases do not exist already as aliases or physical indices
            $indexOrAliasExists = $this->getConnection()->getClient()->indices()->exists(array('index' => $this->readAlias));
            if ($indexOrAliasExists) {
                throw new \RuntimeException(sprintf('Read alias "%s" already exists as an alias or an index', $this->readAlias));
            }
            $indexOrAliasExists = $this->getConnection()->getClient()->indices()->exists(array('index' => $this->writeAlias));
            if ($indexOrAliasExists) {
                throw new \RuntimeException(sprintf('Write alias "%s" already exists as an alias or an index', $this->writeAlias));
            }

            // Create physical index with a unique name
            $settings['index'] = $this->getBaseIndexName() . '_' . date('YmdHis');
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
            $indexOrAliasExists = $this->getConnection()->getClient()->indices()->exists(array('index' => $this->getBaseIndexName()));
            if ($indexOrAliasExists) {
                throw new \RuntimeException(sprintf('Index "%s" already exists as an alias or an index', $this->getBaseIndexName()));
            }
            $this->getConnection()->getClient()->indices()->create($settings);
        }

//        if ($putWarmers) {
//            // Sometimes Elasticsearch gives service unavailable.
//            usleep(200000);
//            $this->putWarmers();
//        }
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
     */
    public function getLiveIndex()
    {
        if (true === $this->getUseAliases()) {
            $indexName = key($this->getConnection()->getClient()->indices()->getAlias(array('name' => $this->readAlias)));
        } else {
            $indexName = $this->getBaseIndexName();
        }

        return $indexName;
    }

    /**
     * Rebuilds ES Index
     *
     * @throws \Exception
     */
    public function rebuildIndex()
    {
        if (false === $this->getUseAliases()) {
            throw new \Exception('Index rebuilding is not supported, unless you use aliases');
        }

        $oldIndex = $this->getLiveIndex();

        // Create a new index
        $settings = $this->indexSettings;
        $settings['index'] = $this->getBaseIndexName() . '_' . date('YmdHis');
        $this->getConnection()->getClient()->indices()->create($settings);
        // Point write alias to the new index as well
        $setAliasParams = [
            'body' => [
                'actions' => [
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

        // Override the write alias with the new physical index name, so rebuilding only happens in the new index
        $this->setWriteAlias($settings['index']);

        dump($this->metadataCollection->getTypes());die;

    }

    /**
     * Adds document to next flush.
     *
     * @param DocumentInterface $document
     */
    public function persist(DocumentInterface $document)
    {
        $this->dispatchEvent(
            Events::PRE_PERSIST,
            new ElasticsearchPersistEvent($this->getConnection(), $document)
        );

        $mapping = $this->getDocumentMapping($document);
        $documentArray = $this->getConverter()->convertToArray($document);

        $this->getConnection()->addBulkOperation(
            'index',
            $this->writeAlias,
            $mapping->getType(),
            $documentArray
        );

        $this->dispatchEvent(
            Events::POST_PERSIST,
            new ElasticsearchPersistEvent($this->getConnection(), $document)
        );
    }

    /**
     * Commits bulk batch to elasticsearch index.
     */
    public function commit()
    {
        $this->dispatchEvent(
            Events::PRE_COMMIT,
            new ElasticsearchCommitEvent($this->getConnection())
        );

        $this->getConnection()->commit();

        $this->dispatchEvent(
            Events::POST_COMMIT,
            new ElasticsearchCommitEvent($this->getConnection())
        );
    }

//    /**
//     * Returns repository metadata for document.
//     *
//     * @param object $document
//     *
//     * @return ClassMetadata|null
//     */
//    public function getDocumentMapping($document)
//    {
//        foreach ($this->getBundlesMapping() as $repository) {
//            if (in_array(get_class($document), [$repository->getNamespace(), $repository->getProxyNamespace()])) {
//                return $repository;
//            }
//        }
//
//        return null;
//    }

//    /**
//     * Returns bundles mapping.
//     *
//     * @param array $repositories
//     *
//     * @return ClassMetadata[]
//     */
//    public function getBundlesMapping($repositories = [])
//    {
//        return $this->metadataCollection->getMetadata($repositories);
//    }
//
//    /**
//     * @return array
//     */
//    public function getTypesMapping()
//    {
//        return $this->metadataCollection->getTypesMap();
//    }
//
//    /**
//     * Checks if specified repository and type is defined, throws exception otherwise.
//     *
//     * @param string $type
//     *
//     * @throws \InvalidArgumentException
//     */
//    private function checkRepositoryType(&$type)
//    {
//        $mapping = $this->getBundlesMapping();
//
//        if (array_key_exists($type, $mapping)) {
//            return;
//        }
//
//        if (array_key_exists($type . 'Document', $mapping)) {
//            $type .= 'Document';
//
//            return;
//        }
//
//        $exceptionMessage = "Undefined repository `{$type}`, valid repositories are: `" .
//            join('`, `', array_keys($this->getBundlesMapping())) . '`.';
//        throw new \InvalidArgumentException($exceptionMessage);
//    }
//
//    /**
//     * Returns converter instance.
//     *
//     * @return Converter
//     */
//    private function getConverter()
//    {
//        if (!$this->converter) {
//            $this->converter = new Converter($this->getTypesMapping(), $this->getBundlesMapping());
//        }
//
//        return $this->converter;
//    }

    /**
     * Dispatches an event, if eventDispatcher is set.
     *
     * @param string $eventName
     * @param Event  $event
     */
    private function dispatchEvent($eventName, Event $event)
    {
        if ($this->eventDispatcher !== null) {
            $this->eventDispatcher->dispatch($eventName, $event);
        }
    }
}
