<?php

namespace Sineflow\ElasticsearchBundle\Document;

use Sineflow\ElasticsearchBundle\Annotation as ES;

/**
 * Document abstraction which introduces mandatory fields for the document.
 */
abstract class AbstractDocument implements DocumentInterface
{
    /**
     * @var string
     *
     * @ES\Property(type="string", name="_id")
     */
    public $id;

    /**
     * @var string
     *
     * @ES\Property(type="float", name="_score")
     */
    public $score;

    /**
     * @var string
     *
     * @ES\Property(type="string", name="_parent")
     */
    public $parent;

    /**
     * When document is cloned id is set to null.
     */
    public function __clone()
    {
        $this->id = null;
    }

}
