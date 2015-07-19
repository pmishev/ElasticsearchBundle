<?php

namespace Sineflow\ElasticsearchBundle\Mapping;

use Doctrine\Common\Inflector\Inflector;

/**
 * Utility for string case transformations.
 */
class Caser
{
    /**
     * Transforms string to camel case.
     *
     * @param string $string Text to transform.
     *
     * @return string
     */
    public static function camel($string)
    {
        return Inflector::camelize($string);
    }

    /**
     * Transforms string to snake case.
     *
     * @param string $string Text to transform.
     *
     * @return string
     */
    public static function snake($string)
    {
        $string = preg_replace('#([A-Z\d]+)([A-Z][a-z])#', '\1_\2', self::camel($string));
        $string = preg_replace('#([a-z\d])([A-Z])#', '\1_\2', $string);

        return strtolower(strtr($string, '-', '_'));
    }
}
