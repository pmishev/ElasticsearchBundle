<?php

namespace Sineflow\ElasticsearchBundle\DSL\Query\Span;

use Sineflow\ElasticsearchBundle\DSL\ParametersTrait;

/**
 * Elasticsearch span first query.
 */
class SpanFirstQuery implements SpanQueryInterface
{
    use ParametersTrait;

    /**
     * @var SpanQueryInterface
     */
    private $query;

    /**
     * @var int
     */
    private $end;

    /**
     * @param SpanQueryInterface $query
     * @param int                $end
     * @param array              $parameters
     *
     * @throws \LogicException
     */
    public function __construct(SpanQueryInterface $query, $end, array $parameters = [])
    {
        $this->query = $query;
        $this->end = $end;
        $this->setParameters($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'span_first';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $query = [];
        $query['match'] = [$this->query->getType() => $this->query->toArray()];
        $query['end'] = $this->end;
        $output = $this->processArray($query);

        return $output;
    }
}
