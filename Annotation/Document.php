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
    public $dateFormat;

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
            '_ttl' => $this->ttl,
            '_all' => $this->all,
            '_timestamp' => $this->timestamp,
            'dynamic_templates' => $this->dynamicTemplates,
            'transform' => $this->transform,
            'dynamic_date_formats' => $this->dateFormat,
        ];
    }
}
