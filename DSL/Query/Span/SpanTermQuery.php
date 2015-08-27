<?php

namespace Sineflow\ElasticsearchBundle\DSL\Query\Span;

use Sineflow\ElasticsearchBundle\DSL\Query\TermQuery;

/**
 * Elasticsearch span_term query class.
 */
class SpanTermQuery extends TermQuery implements SpanQueryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'span_term';
    }
}
