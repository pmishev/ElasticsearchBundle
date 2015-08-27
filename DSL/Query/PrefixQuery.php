<?php

namespace Sineflow\ElasticsearchBundle\DSL\Query;

use Sineflow\ElasticsearchBundle\DSL\Filter\PrefixFilter;

/**
 * Represents Elasticsearch "prefix" query.
 */
class PrefixQuery extends PrefixFilter
{
    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $query = [
            'value' => $this->value,
        ];

        $output = [
            $this->field => $this->processArray($query),
        ];

        return $output;
    }
}
