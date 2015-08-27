<?php

namespace Sineflow\ElasticsearchBundle\DSL\Query;

use Sineflow\ElasticsearchBundle\DSL\BuilderInterface;
use Sineflow\ElasticsearchBundle\DSL\ParametersTrait;

/**
 * Elasticsearch multi_match query class.
 */
class MultiMatchQuery implements BuilderInterface
{
    use ParametersTrait;

    /**
     * @var array
     */
    private $fields = [];

    /**
     * @var string
     */
    private $query;

    /**
     * @param array  $fields
     * @param string $query
     * @param array  $parameters
     */
    public function __construct(array $fields, $query, array $parameters = [])
    {
        $this->fields = $fields;
        $this->query = $query;
        $this->setParameters($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'multi_match';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $query = [
            'fields' => $this->fields,
            'query' => $this->query,
        ];

        $output = $this->processArray($query);

        return $output;
    }
}
