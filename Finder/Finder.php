<?php

namespace Sineflow\ElasticsearchBundle\Finder;
use ONGR\ElasticsearchDSL\Search;
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
     * Executes a search and return results
     *
     * @param string[] $documentClasses
     * @param Search   $search
     * @param int      $resultsType
     * @return mixed
     */
    public function find(array $documentClasses, Search $search, $resultsType = self::RESULTS_OBJECT)
    {
        $client = $this->getConnection($documentClasses)->getClient();

        $allDocumentClassToIndexMappings = $this->documentMetadataCollection->getDocumentClassesIndices();
        $documentClassToIndexMap = array_intersect_key($allDocumentClassToIndexMappings, array_flip($documentClasses));
        $typesMetadataCollection = new IndicesAndTypesMetadataCollection();

        $getLiveIndices = false;
        if (self::RESULTS_OBJECT == $resultsType) {
            $classToTypeMap = $this->documentMetadataCollection->getClassToTypeMap($documentClasses);
            // If there are duplicate type names across the indices we're querying
            if (count($classToTypeMap) > count(array_unique($classToTypeMap))) {
                // We'll need to get the live index name for each type, so we can correctly map the results to the appropriate objects
                $getLiveIndices = true;
            }
        }

        $indices = [];
        foreach ($documentClassToIndexMap as $documentClass => $indexManagerName) {
            // Build mappings of indices and types to metadata, for the Converter
            $liveIndex = $getLiveIndices ? $this->indexManagerFactory->get($indexManagerName)->getLiveIndex() : null;
            $documentMetadata = $this->documentMetadataCollection->getDocumentMetadata($documentClass);
            $typesMetadataCollection->setTypeMetadata($documentMetadata, $liveIndex);

            $indices[] = $this->indexManagerFactory->get($indexManagerName)->getReadAlias();
            $types[] = $documentMetadata->getType();
        }

        $params = [];
        $params['index'] = array_unique($indices);
        $params['type'] = $types;
        $params['body'] = $search->toArray();

//        $queryStringParams = $search->getQueryParams();
//        if (!empty($queryStringParams)) {
//            $params = array_merge($queryStringParams, $params);
//        }

        $raw = $client->search($params);

        return $this->parseResult($raw, $resultsType, $typesMetadataCollection);
    }

    private function parseResult($raw, $resultsType, IndicesAndTypesMetadataCollection $typesMetadataCollection)
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

                return new DocumentIterator(
                    $raw,
                    $typesMetadataCollection,
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
                throw new \InvalidArgumentException(sprintf('All searched types must be in indexes within the same connection'));
            }
            $connection = $indexManager->getConnection();
        }

        return $connection;
    }
}

