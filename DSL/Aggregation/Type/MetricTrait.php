<?php

namespace Sineflow\ElasticsearchBundle\DSL\Aggregation\Type;

/**
 * Trait used by Aggregations which do not support nesting.
 */
trait MetricTrait
{
    /**
     * Metric aggregations does not support nesting.
     *
     * @return bool
     */
    protected function supportsNesting()
    {
        return false;
    }
}
