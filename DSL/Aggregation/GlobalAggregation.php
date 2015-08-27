<?php

namespace Sineflow\ElasticsearchBundle\DSL\Aggregation;

use Sineflow\ElasticsearchBundle\DSL\Aggregation\Type\BucketingTrait;

/**
 * Class representing GlobalAggregation.
 */
class GlobalAggregation extends AbstractAggregation
{
    use BucketingTrait;

    /**
     * {@inheritdoc}
     */
    public function setField($field)
    {
        throw new \LogicException("Global aggregation, doesn't support `field` parameter");
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'global';
    }

    /**
     * {@inheritdoc}
     */
    public function getArray()
    {
        return new \stdClass();
    }
}
