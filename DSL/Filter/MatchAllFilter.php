<?php

namespace Sineflow\ElasticsearchBundle\DSL\Filter;

use Sineflow\ElasticsearchBundle\DSL\BuilderInterface;

/**
 * Represents Elasticsearch "match_all" filter.
 *
 * A filter matches on all documents.
 */
class MatchAllFilter implements BuilderInterface
{
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
        return new \stdClass();
    }
}
