<?php

namespace Sineflow\ElasticsearchBundle\DSL\Aggregation;

/**
 * Class representing Avg Aggregation.
 */
class AvgAggregation extends StatsAggregation
{
    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'avg';
    }
}
