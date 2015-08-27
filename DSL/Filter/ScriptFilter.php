<?php

namespace Sineflow\ElasticsearchBundle\DSL\Filter;

use Sineflow\ElasticsearchBundle\DSL\BuilderInterface;
use Sineflow\ElasticsearchBundle\DSL\ParametersTrait;

/**
 * Represents Elasticsearch "script" filter.
 *
 * Allows to define scripts as filters.
 */
class ScriptFilter implements BuilderInterface
{
    use ParametersTrait;

    /**
     * @var string
     */
    private $script;

    /**
     * @param string $script     Script.
     * @param array  $parameters Optional parameters.
     */
    public function __construct($script, array $parameters = [])
    {
        $this->script = $script;
        $this->setParameters($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'script';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $query = ['script' => $this->script];

        $output = $this->processArray($query);

        return $output;
    }
}
