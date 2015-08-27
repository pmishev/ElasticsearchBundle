<?php

namespace Sineflow\ElasticsearchBundle\DSL\Query;

use Sineflow\ElasticsearchBundle\DSL\BuilderInterface;
use Sineflow\ElasticsearchBundle\DSL\ParametersTrait;

/**
 * Elasticsearch match_all query class.
 */
class MatchAllQuery implements BuilderInterface
{
    use ParametersTrait;

    /**
     * @param array $parameters Additional parameters.
     */
    public function __construct(array $parameters = [])
    {
        $this->setParameters($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'match_all';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        if (count($this->getParameters()) > 0) {
            return $this->getParameters();
        }

        return new \stdClass();
    }
}
