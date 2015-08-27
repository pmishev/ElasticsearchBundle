<?php

namespace Sineflow\ElasticsearchBundle\DSL\Query\Span;

use Sineflow\ElasticsearchBundle\DSL\ParametersTrait;

/**
 * Elasticsearch span or query.
 */
class SpanOrQuery implements SpanQueryInterface
{
    use ParametersTrait;

    /**
     * @var SpanQueryInterface[]
     */
    private $queries = [];

    /**
     * @param array $parameters
     */
    public function __construct(array $parameters = [])
    {
        $this->setParameters($parameters);
    }

    /**
     * Add span query.
     *
     * @param SpanQueryInterface $query
     *
     * @return $this
     */
    public function addQuery(SpanQueryInterface $query)
    {
        $this->queries[] = $query;

        return $this;
    }

    /**
     * @return SpanQueryInterface[]
     */
    public function getQueries()
    {
        return $this->queries;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'span_or';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $query = [];
        foreach ($this->queries as $type) {
            $query['clauses'][] = [$type->getType() => $type->toArray()];
        }
        $output = $this->processArray($query);

        return $output;
    }
}
