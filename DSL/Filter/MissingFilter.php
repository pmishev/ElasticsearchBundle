<?php

namespace Sineflow\ElasticsearchBundle\DSL\Filter;

use Sineflow\ElasticsearchBundle\DSL\BuilderInterface;
use Sineflow\ElasticsearchBundle\DSL\ParametersTrait;

/**
 * Represents Elasticsearch "missing" filter.
 */
class MissingFilter implements BuilderInterface
{
    use ParametersTrait;

    /**
     * @var string
     */
    private $field;

    /**
     * @param string $field      Field name.
     * @param array  $parameters Optional parameters.
     */
    public function __construct($field, array $parameters = [])
    {
        $this->field = $field;
        $this->setParameters($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'missing';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $query = [];
        $query['field'] = $this->field;

        $output = $this->processArray($query);

        return $output;
    }
}
