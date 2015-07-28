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
     * @var bool
     */
    public $create = true;

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
     * @var bool
     */
    public $enabled;

    /**
     * @var array
     */
    public $all;

    /**
     * @var string
     */
    public $dynamic;

    /**
     * @var array
     */
    public $transform;

    /**
     * @var array
     */
    public $dynamicDateFormats;

    /**
     * {@inheritdoc}
     */
    public function dump(array $exclude = [])
    {
        return array_diff_key(
            [
                '_ttl' => $this->ttl,
                '_all' => $this->all,
                'enabled' => $this->enabled,
                'dynamic' => $this->dynamic,
                'transform' => $this->transform,
                'dynamic_date_formats' => $this->dynamicDateFormats,
            ],
            $exclude
        );
    }
}
