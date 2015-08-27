<?php

namespace Sineflow\ElasticsearchBundle\DSL\Filter;

use Sineflow\ElasticsearchBundle\DSL\BuilderInterface;
use Sineflow\ElasticsearchBundle\DSL\ParametersTrait;

/**
 * Represents Elasticsearch "ids" filter.
 */
class IdsFilter implements BuilderInterface
{
    use ParametersTrait;

    /**
     * @var string[]
     */
    private $values;

    /**
     * @param string[] $values     Ids' values.
     * @param array    $parameters Optional parameters.
     */
    public function __construct($values, array $parameters = [])
    {
        $this->values = $values;
        $this->setParameters($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'ids';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $query = [];
        $query['values'] = $this->values;

        $output = $this->processArray($query);

        return $output;
    }
}
