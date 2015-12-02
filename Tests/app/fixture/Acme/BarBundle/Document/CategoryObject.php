<?php

namespace Sineflow\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document;

use Sineflow\ElasticsearchBundle\Annotation as ES;

/**
 * Category document for testing.
 *
 * @ES\Object
 */
class CategoryObject
{
    /**
     * @var string Field without ESB annotation, should not be indexed.
     */
    public $withoutAnnotation;

    /**
     * @var string
     * @ES\Property(type="string", name="title", options={"index"="not_analyzed"})
     */
    public $title;
}
