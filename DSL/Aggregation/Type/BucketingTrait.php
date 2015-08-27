<?php

namespace Sineflow\ElasticsearchBundle\DSL\Aggregation\Type;

/**
 * Trait used by Aggregations which supports nesting.
 */
trait BucketingTrait
{
    /**
     * Bucketing aggregations supports nesting.
     *
     * @return bool
     */
    protected function supportsNesting()
    {
        return true;
    }
}
