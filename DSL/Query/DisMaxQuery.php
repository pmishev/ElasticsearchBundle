<?php

namespace Sineflow\ElasticsearchBundle\DSL\Query;

use Sineflow\ElasticsearchBundle\DSL\BuilderInterface;
use Sineflow\ElasticsearchBundle\DSL\ParametersTrait;

/**
 * Elasticsearch dis max query class.
 */
class DisMaxQuery implements BuilderInterface
{
    use ParametersTrait;

    /**
     * @var BuilderInterface[]
     */
    private $queries = [];

    /**
     * Initializes Dis Max query.
     *
     * @param array $parameters
     */
    public function __construct(array $parameters = [])
    {
        $this->setParameters($parameters);
    }

    /**
     * Add query.
     *
     * @param BuilderInterface $query
     *
     * @return DisMaxQuery
     */
    public function addQuery(BuilderInterface $query)
    {
        $this->queries[] = $query;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'dis_max';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $query = [];
        foreach ($this->queries as $type) {
            $query['queries'][] = [$type->getType() => $type->toArray()];
        }
        $output = $this->processArray($query);

        return $output;
    }
}
