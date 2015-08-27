<?php

namespace Sineflow\ElasticsearchBundle\DSL\Filter;

use Sineflow\ElasticsearchBundle\DSL\BuilderInterface;
use Sineflow\ElasticsearchBundle\DSL\ParametersTrait;

/**
 * Represents Elasticsearch "prefix" filter.
 *
 * Filters documents that have fields containing terms with a specified prefix.
 */
class PrefixFilter implements BuilderInterface
{
    use ParametersTrait;

    /**
     * @var string
     */
    protected $field;

    /**
     * @var string
     */
    protected $value;

    /**
     * @param string $field      Field name.
     * @param string $value      Value.
     * @param array  $parameters Optional parameters.
     */
    public function __construct($field, $value, array $parameters = [])
    {
        $this->field = $field;
        $this->value = $value;
        $this->setParameters($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'prefix';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $query = [$this->field => $this->value];

        $output = $this->processArray($query);

        return $output;
    }
}
