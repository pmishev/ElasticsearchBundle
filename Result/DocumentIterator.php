<?php

namespace Sineflow\ElasticsearchBundle\Result;

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

    /**
     * Returns count of records found by given query.
     *
     * @return int
     */
    public function getTotalCount()
    {
        return $this->rawData['hits']['total'];
    }
}
