<?php

namespace Sineflow\ElasticsearchBundle\DSL\Query;

use Sineflow\ElasticsearchBundle\DSL\BuilderInterface;
use Sineflow\ElasticsearchBundle\DSL\ParametersTrait;

/**
 * Regexp query class.
 */
class RegexpQuery implements BuilderInterface
{
    use ParametersTrait;

    /**
     * @var string Field to be queried.
     */
    private $field;

    /**
     * @var string The actual regexp value to be used.
     */
    private $regexpValue;

    /**
     * @param string $field
     * @param string $regexpValue
     * @param array  $parameters
     */
    public function __construct($field, $regexpValue, array $parameters = [])
    {
        $this->field = $field;
        $this->regexpValue = $regexpValue;
        $this->setParameters($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'regexp';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $query = [
            'value' => $this->regexpValue,
        ];

        $output = [
            $this->field => $this->processArray($query),
        ];

        return $output;
    }
}
