<?php

namespace Sineflow\ElasticsearchBundle\DSL\Query;

use Sineflow\ElasticsearchBundle\DSL\BuilderInterface;

/**
 * Elasticsearch nested query class.
 */
class NestedQuery implements BuilderInterface
{
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
     */
    public function __construct($path, BuilderInterface $query)
    {
        $this->path = $path;
        $this->query = $query;
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
        return [
            'path' => $this->path,
            'query' => [
                $this->query->getType() => $this->query->toArray(),
            ],
        ];
    }
}
