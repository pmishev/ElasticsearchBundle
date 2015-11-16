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
     * @var string
     *
     * @Required
     * @Enum({"string", "boolean", "integer", "float", "date", "object", "nested", "geo_point", "geo_shape", "ip"})
     */
    public $type;

    /**
     * The object name must be defined, if type is 'object' or 'nested'
     *
     * @var string Object name to map.
     */
    public $objectName;

    /**
     * Defines if related object will have one or multiple values.
     * If this value is set to true, ObjectIterator will be provided in the result, as opposed to a Document object
     *
     * @var bool
     */
    public $multiple;

    /**
     * Settings directly passed to Elasticsearch client as-is
     *
     * @var array
     */
    public $options;


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
            array_flip(['name', 'objectName', 'multiple', 'options'])
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
