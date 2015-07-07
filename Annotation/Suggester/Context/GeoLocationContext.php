<?php

namespace Sineflow\ElasticsearchBundle\Annotation\Suggester\Context;

/**
 * Class for geo location context annotations used in context suggester.
 *
 * @Annotation
 * @Target("ANNOTATION")
 */
class GeoLocationContext extends AbstractContext
{
    /**
     * @var array
     */
    public $precision;

    /**
     * @var bool
     */
    public $neighbors;

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'geo';
    }
}
