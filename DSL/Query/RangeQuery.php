<?php

namespace Sineflow\ElasticsearchBundle\DSL\Query;

use Sineflow\ElasticsearchBundle\DSL\BuilderInterface;
use Sineflow\ElasticsearchBundle\DSL\ParametersTrait;

/**
 * Range query class.
 */
class RangeQuery implements BuilderInterface
{
    use ParametersTrait;

    /**
     * Range control names.
     */
    const LT = 'lt';
    const GT = 'gt';
    const LTE = 'lte';
    const GTE = 'gte';

    /**
     * @var string Field name.
     */
    private $field;

    /**
     * @param string $field
     * @param array  $parameters
     */
    public function __construct($field, array $parameters = [])
    {
        $this->setParameters($parameters);

        if ($this->hasParameter(self::GTE) && $this->hasParameter(self::GT)) {
            unset($this->parameters[self::GT]);
        }

        if ($this->hasParameter(self::LTE) && $this->hasParameter(self::LT)) {
            unset($this->parameters[self::LT]);
        }

        $this->field = $field;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'range';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $output = [
            $this->field => $this->getParameters(),
        ];

        return $output;
    }
}
