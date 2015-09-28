<?php

namespace Sineflow\ElasticsearchBundle\DTO;

/**
 * Class MLProperty
 *
 * Represents a multi-language property within a document entity
 */
class MLProperty
{
    /**
     * @var string[]
     */
    private $values = [];

    /**
     * Set value of property in given language
     *
     * @param string $value
     * @param string $language
     */
    public function set($value, $language)
    {
        $this->values[$language] = $value;
    }

    /**
     * @param string $language
     * @return null|string
     */
    public function get($language)
    {
        return isset($this->values[$language]) ? $this->values[$language] : null;
    }
}
