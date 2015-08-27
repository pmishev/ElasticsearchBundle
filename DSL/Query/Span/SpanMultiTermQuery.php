<?php

namespace Sineflow\ElasticsearchBundle\DSL\Query\Span;

use Sineflow\ElasticsearchBundle\DSL\BuilderInterface;
use Sineflow\ElasticsearchBundle\DSL\ParametersTrait;

/**
 * Elasticsearch span multi term query.
 */
class SpanMultiTermQuery implements SpanQueryInterface
{
    use ParametersTrait;

    /**
     * @var BuilderInterface
     */
    private $query;

    /**
     * Accepts one of fuzzy, prefix, term range, wildcard, regexp query.
     *
     * @param BuilderInterface $query
     * @param array            $parameters
     */
    public function __construct(BuilderInterface $query, array $parameters = [])
    {
        $this->query = $query;
        $this->setParameters($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'span_multi';
    }

    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException
     */
    public function toArray()
    {
        $query = [];
        $query['match'] = [$this->query->getType() => $this->query->toArray()];
        $output = $this->processArray($query);

        return $output;
    }
}
