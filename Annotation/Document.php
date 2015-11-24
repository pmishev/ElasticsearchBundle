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
     * Document parent type.
     *
     * @var string
     */
    public $parent;

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
    public $dynamicDateFormats;

    /**
     * {@inheritdoc}
     */
    public function dump(array $options = [])
    {
        return [
            '_parent' => $this->parent,
            '_all' => $this->all,
            'dynamic_templates' => $this->dynamicTemplates,
            'dynamic_date_formats' => $this->dynamicDateFormats,
        ];
    }
}
