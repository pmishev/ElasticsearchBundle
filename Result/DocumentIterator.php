<?php

namespace Sineflow\ElasticsearchBundle\Result;

//use ONGR\ElasticsearchBundle\Document\DocumentInterface;
//use ONGR\ElasticsearchBundle\DSL\Aggregation\AbstractAggregation;
//use ONGR\ElasticsearchBundle\Result\Aggregation\AggregationIterator;
//use ONGR\ElasticsearchBundle\Result\Suggestion\SuggestionIterator;
use Sineflow\ElasticsearchBundle\DTO\IndicesAndTypesMetadataCollection;

/**
 * This class is able to iterate over Elasticsearch result documents while casting data into models.
 */
class DocumentIterator extends AbstractResultsIterator
{
    /**
     * @var array
     */
    private $rawData;

    /**
     * @var IndicesAndTypesMetadataCollection
     */
    private $typesMetadataCollection;

//    /**
//     * @var AggregationIterator
//     */
//    private $aggregations;
//
//    /**
//     * @var SuggestionIterator
//     */
//    private $suggestions;

    /**
     * @var string
     */
    private $languageSeparator;

    /**
     * Constructor.
     *
     * @param array                             $rawData
     * @param IndicesAndTypesMetadataCollection $typesMetadataCollection
     * @param string                            $languageSeparator
     */
    public function __construct($rawData, IndicesAndTypesMetadataCollection $typesMetadataCollection, $languageSeparator)
    {
        $this->rawData = $rawData;
        $this->typesMetadataCollection = $typesMetadataCollection;
        $this->languageSeparator = $languageSeparator;

        // Alias documents to have shorter path.
        if (isset($rawData['hits']['hits'])) {
            $this->documents = &$rawData['hits']['hits'];
        }
    }

    /**
     * Returns a converter.
     *
     * @param string $index
     * @param string $type
     * @return Converter
     */
    protected function getConverter($index, $type)
    {
        $metadata = $this->typesMetadataCollection->getTypeMetadata($type, $index);
        $converter = new Converter($metadata, $this->languageSeparator);

        return $converter;
    }

    /**
     * Converts raw array to document.
     *
     * @param array $rawData
     *
     * @return DocumentInterface
     *
     * @throws \LogicException
     */
    protected function convertDocument($rawData)
    {
        return $this->getConverter($rawData['_index'], $rawData['_type'])->convertToDocument($rawData);
    }

//    /**
//     * @param string $type
//     *
//     * @return mixed
//     */
//    protected function getMapByType($type)
//    {
//        return $this->bundlesMapping[$this->typesMapping[$type]];
//    }

    /**
     * Returns count of records found by given query.
     *
     * @return int
     */
    public function getTotalCount()
    {
        return $this->rawData['hits']['total'];
    }

//    /**
//     * Returns aggregations.
//     *
//     * @return AggregationIterator
//     */
//    public function getAggregations()
//    {
//        if (isset($this->rawData['aggregations'])) {
//            $data = [];
//
//            foreach ($this->rawData['aggregations'] as $key => $value) {
//                $realKey = substr($key, strlen(AbstractAggregation::PREFIX));
//                $data[$realKey] = $value;
//            }
//
//            unset($this->rawData['aggregations']);
//            $this->aggregations = new AggregationIterator($data, $this->getConverter());
//        } elseif ($this->aggregations === null) {
//            $this->aggregations = new AggregationIterator([]);
//        }
//
//        return $this->aggregations;
//    }
//
//    /**
//     * Returns suggestions.
//     *
//     * @return SuggestionIterator
//     */
//    public function getSuggestions()
//    {
//        if (isset($this->rawData['suggest'])) {
//            $this->suggestions = new SuggestionIterator($this->rawData['suggest']);
//
//            // Clear memory.
//            unset($this->rawData['suggest']);
//        }
//
//        return $this->suggestions;
//    }
}
