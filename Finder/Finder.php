<?php

namespace Sineflow\ElasticsearchBundle\Finder;
use ONGR\ElasticsearchDSL\Search;
use Sineflow\ElasticsearchBundle\Manager\ConnectionManager;
use Sineflow\ElasticsearchBundle\Manager\IndexManagerFactory;
use Sineflow\ElasticsearchBundle\Mapping\DocumentMetadataCollection;

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
     * Finder constructor.
     * @param DocumentMetadataCollection $documentMetadataCollection
     * @param IndexManagerFactory        $indexManagerFactory
     */
    public function __construct(DocumentMetadataCollection $documentMetadataCollection, IndexManagerFactory $indexManagerFactory)
    {
        $this->documentMetadataCollection = $documentMetadataCollection;
        $this->indexManagerFactory = $indexManagerFactory;
    }

    /**
     * Verify that all types are in indices using the same connection object and return that object
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

    /**
     * Executes a search and return results
     *
     * @param string[] $documentClasses
     * @param Search   $search
     * @param int      $resultType
     * @return mixed
     */
    public function find(array $documentClasses, Search $search, $resultType = self::RESULTS_OBJECT)
    {
        $client = $this->getConnection($documentClasses)->getClient();

        $allDocumentClassToIndexMappings = $this->documentMetadataCollection->getDocumentClassesIndices();
        $documentClassToIndexMap = array_intersect_key($allDocumentClassToIndexMappings, array_flip($documentClasses));
        $indices = [];
        foreach ($documentClassToIndexMap as $documentClass => $indexManagerName) {
            $indices[] = $this->indexManagerFactory->get($indexManagerName)->getReadAlias();
            $types[] = $this->documentMetadataCollection->getDocumentMetadata($documentClass)->getType();
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

        dump($raw);

        // TODO: convert to arrays or objects

        return $raw;
    }

}

