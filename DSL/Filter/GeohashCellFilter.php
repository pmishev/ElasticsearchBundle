<?php

namespace Sineflow\ElasticsearchBundle\DSL\Filter;

use Sineflow\ElasticsearchBundle\DSL\BuilderInterface;
use Sineflow\ElasticsearchBundle\DSL\ParametersTrait;

/**
 * Represents Elasticsearch "Geohash Cell" filter.
 */
class GeohashCellFilter implements BuilderInterface
{
    use ParametersTrait;

    /**
     * @var string
     */
    private $field;

    /**
     * @var mixed
     */
    private $location;

    /**
     * @param string $field
     * @param mixed  $location
     * @param array  $parameters
     */
    public function __construct($field, $location, array $parameters = [])
    {
        $this->field = $field;
        $this->location = $location;

        $this->setParameters($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'geohash_cell';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $query = [];
        $query[$this->field] = $this->location;
        $output = $this->processArray($query);

        return $output;
    }
}
