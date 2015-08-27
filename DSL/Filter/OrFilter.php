<?php

namespace Sineflow\ElasticsearchBundle\DSL\Filter;

/**
 * Represents Elasticsearch "or" filter.
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-or-filter.html
 */
class OrFilter extends AndFilter
{
    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'or';
    }
}
