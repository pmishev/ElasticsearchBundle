<?php

namespace Sineflow\ElasticsearchBundle\Annotation;

use Sineflow\ElasticsearchBundle\Mapping\DumperInterface;

/**
 * Annotation to mark a class as an Elasticsearch document.
 *
 * @Annotation
 * @Target("CLASS")
 */
final class Document implements DumperInterface
{
    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $repositoryClass;

    /**
     * Tells elasticsearch from which field to extract a document ID.
     *
     * @var string
     */
    public $idField;

    /**
     * Document parent type.
     *
     * @var string
     */
    public $parent;

    /**
     * @var array
     */
    public $ttl;

    /**
     * @var array
     */
    public $all;

    /**
     * @var array
     */
    public $dynamicTemplates;

    /**
     * @var array
     */
    public $transform;

    /**
     * @var array
     */
    public $dynamicDateFormats;

    /**
     * @var array
     */
    public $timestamp;

    /**
     * {@inheritdoc}
     */
    public function dump(array $options = [])
    {
        return [
            '_id' => $this->idField ? ['path' => $this->idField] : null,
            '_parent' => $this->parent,
            '_ttl' => $this->ttl,
            '_all' => $this->all,
            '_timestamp' => $this->timestamp,
            'dynamic_templates' => $this->dynamicTemplates,
            'transform' => $this->transform,
            'dynamic_date_formats' => $this->dynamicDateFormats,
        ];
    }
}
