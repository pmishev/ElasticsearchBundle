<?php

namespace Sineflow\ElasticsearchBundle\DSL\Filter;

use Sineflow\ElasticsearchBundle\DSL\BuilderInterface;
use Sineflow\ElasticsearchBundle\DSL\ParametersTrait;

/**
 * Represents Elasticsearch "query" filter.
 */
class QueryFilter implements BuilderInterface
{
    use ParametersTrait;

    /**
     * @var BuilderInterface
     */
    private $query;

    /**
     * @param BuilderInterface $query      Query.
     * @param array            $parameters Optional parameters.
     */
    public function __construct($query, array $parameters = [])
    {
        $this->query = $query;
        $this->setParameters($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        if ($this->hasParameter('_cache')) {
            return 'fquery';
        }

        return 'query';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        if ($this->hasParameter('_cache')) {
            $query = [];
            $query['query'] = [$this->query->getType() => $this->query->toArray()];
            $output = $this->processArray($query);

            return $output;
        }

        return [$this->query->getType() => $this->query->toArray()];
    }
}
