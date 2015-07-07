<?php

namespace Sineflow\ElasticsearchBundle\Annotation\Suggester\Context;

/**
 * Class for geo category context annotations used in context suggester.
 *
 * @Annotation
 * @Target("ANNOTATION")
 */
class CategoryContext extends AbstractContext
{
    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'category';
    }
}
