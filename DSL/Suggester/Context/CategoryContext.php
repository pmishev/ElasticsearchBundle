<?php

namespace Sineflow\ElasticsearchBundle\DSL\Suggester\Context;

/**
 * Category context to be used by context suggester.
 */
class CategoryContext extends AbstractContext
{
    /**
     * {@inheritdoc}
     *
     * @return array|string
     */
    public function toArray()
    {
        return $this->getValue();
    }
}
