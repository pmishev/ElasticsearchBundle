<?php

namespace Sineflow\ElasticsearchBundle\Annotation;

use Sineflow\ElasticsearchBundle\Mapping\Caser;
use Sineflow\ElasticsearchBundle\Mapping\DumperInterface;

/**
 * Makes sure that annotations are well formatted.
 */
abstract class AbstractProperty implements DumperInterface
{
    /**
     * @var string
     *
     * @Required
     */
    public $name;

    /**
     * @var array<\Sineflow\ElasticsearchBundle\Annotation\MultiField>
     */
    public $fields;

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
     * Settings directly passed to Elasticsearch client as-is
     *
     * @var array
     */
    public $rawMapping;

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
    public $enabled;

    /**
     * @var string
     */
    public $indexName;

    /**
     * @var string
     */
    public $format;

    /**
     * @var mixed
     */
    public $nullValue;

    /**
     * {@inheritdoc}
     */
    public function dump(array $options = [])
    {
        $array = array_diff_key(
            // Remove properties with no value set
            array_filter(
                get_object_vars($this),
                function ($value) {
                    return !is_null($value);
                }
            ),
            // Remove system properties, which are not a part of the Elasticsearch mapping
            array_flip(['name', 'objectName', 'multiple', 'rawMapping'])
        );

        $result = array_combine(
            array_map(
                function ($key) {
                    return Caser::snake($key);
                },
                array_keys($array)
            ),
            array_values($array)
        );

        return $result;
    }
}
