<?php

namespace Sineflow\ElasticsearchBundle\Tests\app\fixture\Acme\FooBundle\Document;

use Sineflow\ElasticsearchBundle\Annotation as ES;
use Sineflow\ElasticsearchBundle\Document\AbstractDocument;

/**
 * Testing document for representing media.
 *
 * @ES\Document(type="customer");
 */
class Customer extends AbstractDocument
{
    /**
     * Test adding raw mapping.
     *
     * @var string
     *
     * @ES\Property(name="name", type="string", options={"index"="not_analyzed"})
     */
    public $name;
}
