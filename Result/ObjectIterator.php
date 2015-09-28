<?php

namespace Sineflow\ElasticsearchBundle\Result;

/**
 * ObjectIterator class.
 */
class ObjectIterator extends AbstractResultsIterator
{
    /**
     * @var array property metadata information.
     */
    private $propertyMetadata;

    /**
     * @var Converter
     */
    private $converter;

    /**
     * Constructor.
     *
     * @param Converter $converter
     * @param array     $rawData
     * @param array     $propertyMetadata
     */
    public function __construct($converter, $rawData, $propertyMetadata)
    {
        $this->converter = $converter;
        $this->propertyMetadata = $propertyMetadata;
        $this->converted = [];

        // Alias documents to have shorter path.
        $this->documents = &$rawData;
    }

    /**
     * {@inheritdoc}
     */
    protected function convertDocument($rawData)
    {
        return $this->converter->assignArrayToObject(
            $rawData,
            new $this->propertyMetadata['className'](),
            $this->propertyMetadata['propertiesMetadata']
        );
    }
}
