<?php

namespace Sineflow\ElasticsearchBundle\Annotation\Suggester\Context;

use Sineflow\ElasticsearchBundle\Mapping\DumperInterface;

/**
 * Abstract class for various context annotations.
 */
abstract class AbstractContext implements DumperInterface
{
    /**
     * @var array
     */
    public $default;

    /**
     * @var string
     *
     * @Required
     */
    public $name;

    /**
     * @var string
     */
    public $path;

    /**
     * Returns context type.
     *
     * @return string
     */
    abstract public function getType();

    /**
     * {@inheritdoc}
     */
    public function dump(array $exclude = [])
    {
        $vars = array_diff_key(
            array_filter(get_object_vars($this)),
            array_flip(['name'])
        );

        $vars['type'] = $this->getType();

        return $vars;
    }
}
