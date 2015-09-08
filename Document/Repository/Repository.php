<?php

namespace Sineflow\ElasticsearchBundle\Document\Repository;

use Elasticsearch\Common\Exceptions\Missing404Exception;
use Sineflow\ElasticsearchBundle\Document\DocumentInterface;
use ONGR\ElasticsearchDSL\Query\TermsQuery;
use ONGR\ElasticsearchDSL\Search;
use ONGR\ElasticsearchDSL\Sort\FieldSort;
use ONGR\ElasticsearchDSL\Sort\Sort;
use Sineflow\ElasticsearchBundle\Result\Converter;
use Sineflow\ElasticsearchBundle\Result\DocumentIterator;
use Sineflow\ElasticsearchBundle\Result\DocumentScanIterator;
use Sineflow\ElasticsearchBundle\Result\IndicesResult;
use Sineflow\ElasticsearchBundle\Result\RawResultIterator;
use Sineflow\ElasticsearchBundle\Result\RawResultScanIterator;
use Sineflow\ElasticsearchBundle\Manager\IndexManager;
use Sineflow\ElasticsearchBundle\Mapping\DocumentMetadata;

/**
 * Repository class.
 */
class Repository implements RepositoryInterface
{
    const RESULTS_ARRAY = 'array';
    const RESULTS_OBJECT = 'object';
    const RESULTS_RAW = 'raw';
    const RESULTS_RAW_ITERATOR = 'raw_iterator';

    /**
     * @var IndexManager
     */
    private $manager;

    /**
     * The document class in short notation (e.g. AppBundle:Product)
     *
     * @var string
     */
    private $documentClass;

    /**
     * The type metadata
     *
     * @var DocumentMetadata
     */
    private $metadata;

    /**
     * @var array
     */
    private $fieldsCache = [];

    /**
     * Constructor.
     *
     * @param IndexManager $manager
     * @param string       $documentClass
     */
    public function __construct($manager, $documentClass)
    {
        $this->manager = $manager;
        $this->documentClass = $documentClass;

        // Get the metadata of the document class managed by the repository
        $metadata = $this->getManager()->getDocumentsMetadata([$documentClass]);
        if (empty($metadata)) {
            throw new \InvalidArgumentException(sprintf('Type "%s" is not managed by index "%s"', $documentClass, $manager->getManagerName()));
        }
        $this->metadata = $metadata[$documentClass];
    }

    /**
     * Returns a single document data by ID or null if document is not found.
     *
     * @param string $id         Document Id to find.
     * @param string $resultType Result type returned.
     *
     * @return DocumentInterface|null
     */
    public function find($id, $resultType = self::RESULTS_OBJECT)
    {
        $params = [
            'index' => $this->getManager()->getReadAlias(),
            'type' => $this->metadata->getType(),
            'id' => $id,
        ];

        try {
            $result = $this->getManager()->getConnection()->getClient()->get($params);
        } catch (Missing404Exception $e) {
            return null;
        }

        if ($resultType === self::RESULTS_OBJECT) {
            return (new Converter(
                $this->metadata
            ))->convertToDocument($result);
        }

        return $this->parseResult($result, $resultType, '');
    }

    /**
     * Finds entities by a set of criteria.
     *
     * @param array    $criteria   Example: ['group' => ['best', 'worst'], 'job' => 'medic'].
     * @param array    $orderBy    Example: ['name' => 'ASC', 'surname' => 'DESC'].
     * @param int|null $limit      Example: 5.
     * @param int|null $offset     Example: 30.
     * @param string   $resultType Result type returned.
     *
     * @return array|DocumentIterator The objects.
     *
     * TODO: check if working
     */
    public function findBy(
        array $criteria,
        array $orderBy = [],
        $limit = null,
        $offset = null,
        $resultType = self::RESULTS_OBJECT)
    {
        $search = $this->createSearch();

        if ($limit !== null) {
            $search->setSize($limit);
        }
        if ($offset !== null) {
            $search->setFrom($offset);
        }

        foreach ($criteria as $field => $value) {
            $search->addQuery(new TermsQuery($field, is_array($value) ? $value : [$value]), 'must');
        }

        foreach ($orderBy as $field => $direction) {
            $search->addSort(new FieldSort($field, $direction));
        }

        return $this->execute($search, $resultType);
    }

    /**
     * Finds only one entity by a set of criteria.
     *
     * @param array      $criteria   Example: ['group' => ['best', 'worst'], 'job' => 'medic'].
     * @param array|null $orderBy    Example: ['name' => 'ASC', 'surname' => 'DESC'].
     * @param string     $resultType Result type returned.
     *
     * @return DocumentInterface|null The object.
     * TODO: fix
     */
    public function findOneBy(array $criteria, array $orderBy = [], $resultType = self::RESULTS_OBJECT)
    {
        $search = $this->createSearch();
        $search->setSize(1);

        foreach ($criteria as $field => $value) {
            $search->addQuery(new TermsQuery($field, is_array($value) ? $value : [$value]), 'must');
        }

        foreach ($orderBy as $field => $direction) {
            $search->addSort(new FieldSort($field, $direction));
        }

        $result = $this
            ->getManager()
            ->getConnection()
            ->search($this->types, $this->checkFields($search->toArray()), $search->getQueryParams());

        if ($resultType === self::RESULTS_OBJECT) {
            $rawData = $result['hits']['hits'];
            if (!count($rawData)) {
                return null;
            }

            return (new Converter(
                $this->getManager()->getTypesMapping(),
                $this->getManager()->getBundlesMapping()
            ))->convertToDocument($rawData[0]);
        }

        return $this->parseResult($result, $resultType, '');
    }

    /**
     * Returns search instance.
     *
     * @return Search
     */
    public function createSearch()
    {
        return new Search();
    }

    /**
     * Executes given search.
     *
     * @param Search $search
     * @param string $resultsType
     *
     * @return DocumentIterator|DocumentScanIterator|RawResultIterator|array
     *
     * @throws \Exception
     *
     * TODO: check if working
     *
     */
    public function execute(Search $search, $resultsType = self::RESULTS_OBJECT)
    {
        $results = $this
            ->getManager()
            ->getConnection()
            ->search($this->types, $this->checkFields($search->toArray()), $search->getQueryParams());

        return $this->parseResult($results, $resultsType, $search->getScroll());
    }

    /**
     * Delete by query.
     *
     * @param Search $search
     *
     * @return array
     *
     * TODO: check if working
     *
     */
    public function deleteByQuery(Search $search)
    {
        $results = $this
            ->getManager()
            ->getConnection()
            ->deleteByQuery($this->types, $search->toArray());

        return new IndicesResult($results);
    }

    /**
     * Fetches next set of results.
     *
     * @param string $scrollId
     * @param string $scrollDuration
     * @param string $resultsType
     *
     * @return array|DocumentScanIterator
     *
     * @throws \Exception
     *
     * TODO: check if working
     */
    public function scan(
        $scrollId,
        $scrollDuration = '5m',
        $resultsType = self::RESULTS_OBJECT)
    {
        $results = $this->getManager()->getConnection()->scroll($scrollId, $scrollDuration);

        return $this->parseResult($results, $resultsType, $scrollDuration);
    }

    /**
     * Removes a single document data by ID.
     *
     * @param string $id Document ID to remove.
     *
     * @return array
     *
     * @throws \LogicException
     *
     * TODO: fix
     */
    public function remove($id)
    {
        if (count($this->types) == 1) {
            $params = [
                'index' => $this->getManager()->getConnection()->getIndexName(),
                'type' => $this->types[0],
                'id' => $id,
            ];

            $response = $this->getManager()->getConnection()->getClient()->delete($params);

            return $response;
        } else {
            throw new \LogicException('Only one type must be specified for the find() method');
        }
    }

    /**
     * Checks if all required fields are added.
     *
     * @param array $searchArray
     * @param array $fields
     *
     * @return array
     * TODO: fix
     */
    private function checkFields($searchArray, $fields = ['_parent', '_ttl'])
    {
        if (empty($fields)) {
            return $searchArray;
        }

        // Checks if cache is loaded.
        if (empty($this->fieldsCache)) {
            foreach ($this->getManager()->getBundlesMapping($this->documentClasses) as $ns => $properties) {
                $this->fieldsCache = array_unique(
                    array_merge(
                        $this->fieldsCache,
                        array_keys($properties->getFields())
                    )
                );
            }
        }

        // Adds cached fields to fields array.
        foreach (array_intersect($this->fieldsCache, $fields) as $field) {
            $searchArray['fields'][] = $field;
        }

        // Removes duplicates and checks if its needed to add _source.
        if (!empty($searchArray['fields'])) {
            $searchArray['fields'] = array_unique($searchArray['fields']);
            if (array_diff($searchArray['fields'], $fields) === []) {
                $searchArray['fields'][] = '_source';
            }
        }

        return $searchArray;
    }

    /**
     * Parses raw result.
     *
     * @param array  $raw
     * @param string $resultsType
     * @param string $scrollDuration
     *
     * @return DocumentIterator|DocumentScanIterator|RawResultIterator|array
     *
     * @throws \Exception
     * TODO: fix
     */
    private function parseResult($raw, $resultsType, $scrollDuration)
    {
        switch ($resultsType) {
            case self::RESULTS_OBJECT:
                if (isset($raw['_scroll_id'])) {
                    $iterator = new DocumentScanIterator(
                        $raw,
                        $this->getManager()->getTypesMapping(),
                        $this->getManager()->getBundlesMapping()
                    );
                    $iterator
                        ->setRepository($this)
                        ->setScrollDuration($scrollDuration)
                        ->setScrollId($raw['_scroll_id']);

                    return $iterator;
                }

                return new DocumentIterator(
                    $raw,
                    $this->getManager()->getTypesMapping(),
                    $this->getManager()->getBundlesMapping()
                );
            case self::RESULTS_ARRAY:
                return $this->convertToNormalizedArray($raw);
            case self::RESULTS_RAW:
                return $raw;
            case self::RESULTS_RAW_ITERATOR:
                if (isset($raw['_scroll_id'])) {
                    $iterator = new RawResultScanIterator($raw);
                    $iterator
                        ->setRepository($this)
                        ->setScrollDuration($scrollDuration)
                        ->setScrollId($raw['_scroll_id']);

                    return $iterator;
                }

                return new RawResultIterator($raw);
            default:
                throw new \Exception('Wrong results type selected');
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
     * Returns elasticsearch manager used in the repository.
     *
     * @return IndexManager
     */
    public function getManager()
    {
        return $this->manager;
    }
}
