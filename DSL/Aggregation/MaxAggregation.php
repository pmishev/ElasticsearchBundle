<?php

namespace Sineflow\ElasticsearchBundle\DSL\Aggregation;

/**
 * Class representing Max Aggregation.
 */
class MaxAggregation extends StatsAggregation
{
    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'max';
    }
}
