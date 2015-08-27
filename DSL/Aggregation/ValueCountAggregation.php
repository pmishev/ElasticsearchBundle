<?php

namespace Sineflow\ElasticsearchBundle\DSL\Aggregation;

/**
 * Class representing Value Count Aggregation.
 */
class ValueCountAggregation extends StatsAggregation
{
    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'value_count';
    }
}
