<?php

namespace Sineflow\ElasticsearchBundle\DSL\Filter;

use Sineflow\ElasticsearchBundle\DSL\BuilderInterface;
use Sineflow\ElasticsearchBundle\DSL\DslTypeAwareTrait;
use Sineflow\ElasticsearchBundle\DSL\ParametersTrait;

/**
 * Elasticsearch has_parent filter.
 */
class HasParentFilter implements BuilderInterface
{
    use ParametersTrait;
    use DslTypeAwareTrait;

    /**
     * @var string
     */
    private $parentType;

    /**
     * @var BuilderInterface
     */
    private $query;

    /**
     * @param string           $parentType
     * @param BuilderInterface $query
     * @param array            $parameters
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($parentType, BuilderInterface $query, array $parameters = [])
    {
        $this->parentType = $parentType;
        $this->query = $query;
        $this->setParameters($parameters);
        $this->setDslType('filter');
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'has_parent';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $query = [
            'parent_type' => $this->parentType,
            $this->getDslType() => [$this->query->getType() => $this->query->toArray()],
        ];

        $output = $this->processArray($query);

        return $output;
    }
}
