<?php

namespace Sineflow\ElasticsearchBundle\DSL\Query;

/**
 * Elasticsearch fuzzy_like_this query class.
 */
class FuzzyLikeThisQuery extends FuzzyLikeThisFieldQuery
{
    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'fuzzy_like_this';
    }
}
