<?php

namespace Sineflow\ElasticsearchBundle\DSL\Aggregation;

use Sineflow\ElasticsearchBundle\DSL\Aggregation\Type\BucketingTrait;

/**
 * Class representing missing aggregation.
 */
class MissingAggregation extends AbstractAggregation
{
    use BucketingTrait;

    /**
     * {@inheritdoc}
     */
    public function getArray()
    {
        if ($this->getField()) {
            return ['field' => $this->getField()];
        }
        throw new \LogicException('Missing aggregation must have a field set.');
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'missing';
    }
}
