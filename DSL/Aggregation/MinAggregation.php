<?php

namespace Sineflow\ElasticsearchBundle\DSL\Aggregation;

/**
 * Class representing Min Aggregation.
 */
class MinAggregation extends StatsAggregation
{
    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'min';
    }
}
