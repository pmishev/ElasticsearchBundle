<?php

namespace Sineflow\ElasticsearchBundle\DSL\Aggregation;

use Sineflow\ElasticsearchBundle\DSL\Aggregation\Type\BucketingTrait;

/**
 * Class representing ChildrenAggregation.
 */
class ChildrenAggregation extends AbstractAggregation
{
    use BucketingTrait;

    /**
     * @var string
     */
    private $children;

    /**
     * Return children.
     *
     * @return string
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Sets children.
     *
     * @param string $children
     */
    public function setChildren($children)
    {
        $this->children = $children;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'children';
    }

    /**
     * {@inheritdoc}
     */
    public function getArray()
    {
        if (count($this->getAggregations()) == 0) {
            throw new \LogicException("Children aggregation `{$this->getName()}` has no aggregations added");
        }

        return ['type' => $this->getChildren()];
    }
}
