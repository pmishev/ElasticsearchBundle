<?php

namespace Sineflow\ElasticsearchBundle\DSL\Aggregation;

/**
 * Class representing Sum Aggregation.
 */
class SumAggregation extends StatsAggregation
{
    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'sum';
    }
}
