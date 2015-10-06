<?php

namespace Sineflow\ElasticsearchBundle\Finder;

use Elasticsearch\Common\Exceptions\Missing404Exception;
use Sineflow\ElasticsearchBundle\Manager\ConnectionManager;
use Sineflow\ElasticsearchBundle\Manager\IndexManagerFactory;
use Sineflow\ElasticsearchBundle\Mapping\DocumentMetadataCollection;
use Sineflow\ElasticsearchBundle\DTO\IndicesAndTypesMetadataCollection;
use Sineflow\ElasticsearchBundle\Result\Converter;
use Sineflow\ElasticsearchBundle\Result\DocumentIterator;

/**
 * Finder class for searching in ES indexes
 */
class Finder
{
    const RESULTS_ARRAY = 1;
    const RESULTS_OBJECT = 2;
    const RESULTS_RAW = 3;

    /**
     * @var DocumentMetadataCollection
     */
    private $documentMetadataCollection;

    /**
     * @var IndexManagerFactory
     */
    private $indexManagerFactory;

    /**
     * @var string
     */
    private $languageSeparator;

    /**
     * Finder constructor.
     * @param DocumentMetadataCollection $documentMetadataCollection
     * @param IndexManagerFactory        $indexManagerFactory
     * @param string                     $languageSeparator
     */
    public function __construct(DocumentMetadataCollection $documentMetadataCollection, IndexManagerFactory $indexManagerFactory, $languageSeparator)
    {
        $this->documentMetadataCollection = $documentMetadataCollection;
        $this->indexManagerFactory = $indexManagerFactory;
        $this->languageSeparator = $languageSeparator;
    }

    /**
     * Returns a document by identifier
     *
     * @param string $documentClass In short notation (i.e AppBundle:Product)
     * @param string $id
     * @param int    $resultType
     * @return mixed
     */
    public function get($documentClass, $id, $resultType = self::RESULTS_OBJECT)
    {
        $client = $this->getConnection([$documentClass])->getClient();

        $allDocumentClassToIndexMappings = $this->documentMetadataCollection->getDocumentClassesIndices();
        $indexManagerName = $allDocumentClassToIndexMappings[$documentClass];
        $documentMetadata = $this->documentMetadataCollection->getDocumentMetadata($documentClass);

        $params = [
            'index' => $this->indexManagerFactory->get($indexManagerName)->getReadAlias(),
            'type' => $documentMetadata->getType(),
            'id' => $id,
        ];

        try {
            $raw = $client->get($params);
        } catch (Missing404Exception $e) {
            return null;
        }

        switch ($resultType) {
            case self::RESULTS_OBJECT:
                return (new Converter($documentMetadata, $this->languageSeparator))->convertToDocument($raw);
            case self::RESULTS_ARRAY:
                return $this->convertToNormalizedArray($raw);
            case self::RESULTS_RAW:
                return $raw;
            default:
                throw new \InvalidArgumentException('Wrong result type selected');
        }
    }

    /**
     * Executes a search and return results
     *
     * @param string[] $documentClasses
     * @param array    $searchBody
     * @param int      $resultsType
     * @param array    $additionalRequestParams
     * @return mixed
     */
    public function find(array $documentClasses, array $searchBody, $resultsType = self::RESULTS_OBJECT, array $additionalRequestParams = [])
    {
        $client = $this->getConnection($documentClasses)->getClient();

        $indicesAndTypes = $this->getTargetIndicesAndTypes($documentClasses);

        $params = array_merge($indicesAndTypes, [
            'body' => $searchBody,
        ]);

        if (!empty($additionalRequestParams)) {
            $params = array_merge($additionalRequestParams, $params);
        }

        $raw = $client->search($params);

        return $this->parseResult($raw, $resultsType, $documentClasses);
    }

    /**
     * Returns the number of records matching the given query
     *
     * @param array $documentClasses
     * @param array $searchBody
     * @param array $additionalRequestParams
     * @return int
     */
    public function count(array $documentClasses, array $searchBody, array $additionalRequestParams = [])
    {
        $client = $this->getConnection($documentClasses)->getClient();

        $indicesAndTypes = $this->getTargetIndicesAndTypes($documentClasses);

        $params = array_merge($indicesAndTypes, [
            'body' => $searchBody,
        ]);

        if (!empty($additionalRequestParams)) {
            $params = array_merge($additionalRequestParams, $params);
        }

        $raw = $client->count($params);

        return $raw['count'];

    }

    /**
     * Returns an array with the Elasticsearch indices and types to be queried,
     * based on the given document classes in short notation (AppBundle:Product)
     *
     * @param string[] $documentClasses
     * @return array
     */
    public function getTargetIndicesAndTypes(array $documentClasses)
    {
        $allDocumentClassToIndexMappings = $this->documentMetadataCollection->getDocumentClassesIndices();
        $documentClassToIndexMap = array_intersect_key($allDocumentClassToIndexMappings, array_flip($documentClasses));

        $indices = [];
        $types = [];
        foreach ($documentClassToIndexMap as $documentClass => $indexManagerName) {
            $documentMetadata = $this->documentMetadataCollection->getDocumentMetadata($documentClass);

            $indices[] = $this->indexManagerFactory->get($indexManagerName)->getReadAlias();
            $types[] = $documentMetadata->getType();
        }

        $result = [
            'index' => array_unique($indices),
            'type' => $types,
        ];

        return $result;
    }

    /**
     * Returns a mapping of live indices and types to the document classes in short notation that represent them
     *
     * @param string[] $documentClasses
     * @return IndicesAndTypesMetadataCollection
     */
    private function getIndicesAndTypesMetadataCollection(array $documentClasses)
    {
        $allDocumentClassToIndexMappings = $this->documentMetadataCollection->getDocumentClassesIndices();
        $documentClassToIndexMap = array_intersect_key($allDocumentClassToIndexMappings, array_flip($documentClasses));
        $typesMetadataCollection = new IndicesAndTypesMetadataCollection();

        $getLiveIndices = false;
        $classToTypeMap = $this->documentMetadataCollection->getClassToTypeMap($documentClasses);
        // If there are duplicate type names across the indices we're querying
        if (count($classToTypeMap) > count(array_unique($classToTypeMap))) {
            // We'll need to get the live index name for each type, so we can correctly map the results to the appropriate objects
            $getLiveIndices = true;
        }

        foreach ($documentClassToIndexMap as $documentClass => $indexManagerName) {
            // Build mappings of indices and types to metadata, for the Converter
            $liveIndex = $getLiveIndices ? $this->indexManagerFactory->get($indexManagerName)->getLiveIndex() : null;
            $documentMetadata = $this->documentMetadataCollection->getDocumentMetadata($documentClass);
            $typesMetadataCollection->setTypeMetadata($documentMetadata, $liveIndex);
        }

        return $typesMetadataCollection;
    }

    private function parseResult($raw, $resultsType, array $documentClasses = null)
    {
        switch ($resultsType) {
            case self::RESULTS_OBJECT:
                // TODO: add the DocumentScanIterator
//                if (isset($raw['_scroll_id'])) {
//                    $iterator = new DocumentScanIterator(
//                        $raw,
//                        $this->getManager()->getTypesMapping(),
//                        $this->getManager()->getBundlesMapping()
//                    );
//                    $iterator
//                        ->setRepository($this)
//                        ->setScrollDuration($scrollDuration)
//                        ->setScrollId($raw['_scroll_id']);
//
//                    return $iterator;
//                }

                if (empty($documentClasses)) {
                    throw new \InvalidArgumentException('$documentClasses must be specified when retrieving results as objects');
                }

                return new DocumentIterator(
                    $raw,
                    $this->getIndicesAndTypesMetadataCollection($documentClasses),
                    $this->languageSeparator
                );
            case self::RESULTS_ARRAY:
                return $this->convertToNormalizedArray($raw);
            case self::RESULTS_RAW:
                return $raw;
//            case self::RESULTS_RAW_ITERATOR:
//                if (isset($raw['_scroll_id'])) {
//                    $iterator = new RawResultScanIterator($raw);
//                    $iterator
//                        ->setRepository($this)
//                        ->setScrollDuration($scrollDuration)
//                        ->setScrollId($raw['_scroll_id']);
//
//                    return $iterator;
//                }
//
//                return new RawResultIterator($raw);
            default:
                throw new \InvalidArgumentException('Wrong results type selected');
        }
    }

    /**
     * Normalizes response array.
     *
     * @param array $data
     *
     * @return array
     */
    private function convertToNormalizedArray($data)
    {
        if (array_key_exists('_source', $data)) {
            return $data['_source'];
        }

        $output = [];

        if (isset($data['hits']['hits'][0]['_source'])) {
            foreach ($data['hits']['hits'] as $item) {
                $output[] = $item['_source'];
            }
        } elseif (isset($data['hits']['hits'][0]['fields'])) {
            foreach ($data['hits']['hits'] as $item) {
                $output[] = array_map('reset', $item['fields']);
            }
        }

        return $output;
    }

    /**
     * Verify that all types are in indices using the same connection object and return that object
     *
     * @param array $documentClasses
     * @return ConnectionManager
     */
    private function getConnection(array $documentClasses)
    {
        $connection = null;
        foreach ($documentClasses as $documentClass) {
            $indexManagerName = $this->documentMetadataCollection->getDocumentClassIndex($documentClass);
            $indexManager = $this->indexManagerFactory->get($indexManagerName);
            if (!is_null($connection) && $indexManager->getConnection()->getConnectionName() != $connection->getConnectionName()) {
                throw new \InvalidArgumentException(sprintf('All searched types must be in indices within the same connection'));
            }
            $connection = $indexManager->getConnection();
        }

        return $connection;
    }
}

