<?php

namespace Sineflow\ElasticsearchBundle\Result;

/**
 * ObjectIterator class.
 */
class ObjectIterator extends AbstractResultsIterator
{
    /**
     * @var array Aliases information.
     */
    private $alias;

    /**
     * @var Converter
     */
    private $converter;

    /**
     * Constructor.
     *
     * @param Converter $converter
     * @param array     $rawData
     * @param array     $alias
     */
    public function __construct($converter, $rawData, $alias)
    {
        $this->converter = $converter;
        $this->alias = $alias;
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
            // new $this->alias['proxyNamespace'](),
            new $this->alias['className'](),
            $this->alias['aliases']
        );
    }
}
