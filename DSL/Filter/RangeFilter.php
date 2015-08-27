<?php

namespace Sineflow\ElasticsearchBundle\DSL\Filter;

use Sineflow\ElasticsearchBundle\DSL\BuilderInterface;
use Sineflow\ElasticsearchBundle\DSL\ParametersTrait;

/**
 * Represents Elasticsearch "range" filter.
 *
 * Filters documents with fields that have terms within a certain range.
 */
class RangeFilter implements BuilderInterface
{
    use ParametersTrait;

    /**
     * @var string
     */
    private $field;

    /**
     * @var array
     */
    private $range;

    /**
     * @param string $field      Field name.
     * @param array  $range      Range values.
     * @param array  $parameters Optional parameters.
     */
    public function __construct($field, $range, array $parameters = [])
    {
        $this->field = $field;
        $this->range = $range;
        $this->setParameters($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'range';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $query = [$this->field => $this->range];

        $output = $this->processArray($query);

        return $output;
    }
}
