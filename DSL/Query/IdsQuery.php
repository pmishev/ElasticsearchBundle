<?php

namespace Sineflow\ElasticsearchBundle\DSL\Query;

use Sineflow\ElasticsearchBundle\DSL\BuilderInterface;
use Sineflow\ElasticsearchBundle\DSL\ParametersTrait;

/**
 * Elasticsearch ids query class.
 */
class IdsQuery implements BuilderInterface
{
    use ParametersTrait;

    /**
     * @var array
     */
    private $values;

    /**
     * @param array $values
     * @param array $parameters
     */
    public function __construct(array $values, array $parameters = [])
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
        $query = [
            'values' => $this->values,
        ];

        $output = $this->processArray($query);

        return $output;
    }
}
