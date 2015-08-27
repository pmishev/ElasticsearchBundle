<?php

namespace Sineflow\ElasticsearchBundle\DSL\Filter;

use Sineflow\ElasticsearchBundle\DSL\BuilderInterface;
use Sineflow\ElasticsearchBundle\DSL\DslTypeAwareTrait;
use Sineflow\ElasticsearchBundle\DSL\ParametersTrait;

/**
 * Elasticsearch has_child filter.
 */
class HasChildFilter implements BuilderInterface
{
    use ParametersTrait;
    use DslTypeAwareTrait;

    /**
     * @var string
     */
    private $type;

    /**
     * @var BuilderInterface
     */
    private $query;

    /**
     * @param string           $type
     * @param BuilderInterface $query
     * @param array            $parameters
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($type, BuilderInterface $query, array $parameters = [])
    {
        $this->type = $type;
        $this->query = $query;
        $this->setParameters($parameters);
        $this->setDslType('filter');
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'has_child';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $query = [
            'type' => $this->type,
            $this->getDslType() => [$this->query->getType() => $this->query->toArray()],
        ];

        $output = $this->processArray($query);

        return $output;
    }
}
