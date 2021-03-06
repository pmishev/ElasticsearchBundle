<?php

namespace Sineflow\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document;

use Sineflow\ElasticsearchBundle\Annotation as ES;
use Sineflow\ElasticsearchBundle\Document\AbstractDocument;
use Sineflow\ElasticsearchBundle\Document\MLProperty;

/**
 * Product document for testing.
 *
 * @ES\Document(
 *  type="product",
 *  repositoryClass="Sineflow\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document\Repository\ProductRepository"
 * )
 */
class Product extends AbstractDocument
{
    /**
     * @var string
     * @ES\Property(
     *  type="string",
     *  name="title",
     *  options={
     *    "fields"={
     *        "raw"={"type"="string", "index"="not_analyzed"},
     *        "title"={"type"="string"}
     *    }
     *  }
     * )
     */
    public $title;

    /**
     * @var string
     * @ES\Property(type="string", name="description")
     */
    public $description;

    /**
     * @var ObjCategory
     * @ES\Property(type="object", name="category", objectName="AcmeBarBundle:ObjCategory")
     */
    public $category;

    /**
     * @var ObjCategory[]
     * @ES\Property(type="object", name="related_categories", multiple=true, objectName="AcmeBarBundle:ObjCategory")
     */
    public $relatedCategories;

    /**
     * @var int
     * @ES\Property(type="float", name="price")
     */
    public $price;

    /**
     * @var string
     * @ES\Property(type="geo_point", name="location")
     */
    public $location;

    /**
     * @var string
     * @ES\Property(type="boolean", name="limited")
     */
    public $limited;

    /**
     * @var string
     * @ES\Property(type="date", name="released")
     */
    public $released;

    /**
     * @var MLProperty
     *
     * @ES\Property(
     *  name="ml_info",
     *  type="string",
     *  multilanguage=true,
     *  options={
     *      "analyzer":"{lang}_analyzer",
     *  }
     * )
     */
    public $mlInfo;

    /**
     * @var int
     *
     * @ES\Property(
     *     type="string",
     *     name="pieces_count",
     *     options={
     *        "fields"={
     *          "count"={"type"="token_count", "analyzer"="whitespace"}
     *        }
     *     }
     * )
     */
    public $tokenPiecesCount;
}
