<?php

namespace Sineflow\ElasticsearchBundle\Annotation;

/**
 * Annotation that can be used to define multi-field parameters.
 *
 * @Annotation
 * @Target("ANNOTATION")
 */
final class MultiField extends AbstractProperty
{
    /**
     * @var string
     *
     * @Required
     */
    public $name;

    /**
     * @var string
     *
     * @Required
     */
    public $type;

    /**
     * @var string
     */
    public $index;

    /**
     * @var string
     */
    public $analyzer;

    /**
     * @var string
     */
    public $indexAnalyzer;

    /**
     * @var string
     */
    public $searchAnalyzer;
}
