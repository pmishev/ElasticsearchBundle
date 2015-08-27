<?php

namespace Sineflow\ElasticsearchBundle\DSL\Filter;

use Sineflow\ElasticsearchBundle\DSL\BuilderInterface;
use Sineflow\ElasticsearchBundle\DSL\ParametersTrait;

/**
 * Nested filter implementation.
 */
class NestedFilter implements BuilderInterface
{
    use ParametersTrait;

    /**
     * @var string
     */
    private $path;

    /**
     * @var BuilderInterface
     */
    private $query;

    /**
     * @param string           $path
     * @param BuilderInterface $query
     * @param array            $parameters
     */
    public function __construct($path, BuilderInterface $query, array $parameters = [])
    {
        $this->path = $path;
        $this->query = $query;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'nested';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $query = [
            'path' => $this->path,
            'filter' => [
                $this->query->getType() => $this->query->toArray(),
            ],
        ];

        $output = $this->processArray($query);

        return $output;
    }
}
