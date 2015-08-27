<?php

namespace Sineflow\ElasticsearchBundle\DSL\Aggregation;

use Sineflow\ElasticsearchBundle\DSL\Aggregation\Type\BucketingTrait;

/**
 * Class representing ReverseNestedAggregation.
 */
class ReverseNestedAggregation extends AbstractAggregation
{
    use BucketingTrait;

    /**
     * @var string
     */
    private $path;

    /**
     * Return path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Sets path.
     *
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'reverse_nested';
    }

    /**
     * {@inheritdoc}
     */
    public function getArray()
    {
        if (count($this->getAggregations()) == 0) {
            throw new \LogicException("Reverse Nested aggregation `{$this->getName()}` has no aggregations added");
        }

        $data = new \stdClass();
        if ($this->getPath()) {
            $data = ['path' => $this->getPath()];
        }

        return $data;
    }
}
