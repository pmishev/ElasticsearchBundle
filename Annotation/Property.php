<?php

namespace Sineflow\ElasticsearchBundle\Annotation;

/**
 * Annotation used to check mapping type during the parsing process.
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class Property extends AbstractProperty
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

    /**
     * @var bool
     */
    public $includeInAll;

    /**
     * @var float
     */
    public $boost;

    /**
     * @var bool
     */
    public $payloads;

    /**
     * @var bool
     */
    public $enabled;

    /**
     * @var array<\Sineflow\ElasticsearchBundle\Annotation\MultiField>
     */
    public $fields;

    /**
     * @var array
     */
    public $fieldData;

    /**
     * @var string Object name to map.
     */
    public $objectName;

    /**
     * Defines if related object will have one or multiple values.
     *
     * @var bool OneToOne or OneToMany.
     */
    public $multiple;

    /**
     * @var int
     */
    public $ignoreAbove;

    /**
     * @var bool
     */
    public $store;

    /**
     * @var string
     */
    public $indexName;

    /**
     * @var string
     */
    public $format;

    /**
     * @var array
     */
    public $raw;
}
