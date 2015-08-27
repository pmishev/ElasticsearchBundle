<?php

namespace Sineflow\ElasticsearchBundle\DSL\Filter;

use Sineflow\ElasticsearchBundle\DSL\BuilderInterface;
use Sineflow\ElasticsearchBundle\DSL\ParametersTrait;

/**
 * Represents Elasticsearch "not" filter.
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-not-filter.html
 */
class NotFilter implements BuilderInterface
{
    use ParametersTrait;

    /**
     * @var BuilderInterface
     */
    private $filter;

    /**
     * @param BuilderInterface $filter     Filter.
     * @param array            $parameters Optional parameters.
     */
    public function __construct(BuilderInterface $filter = null, array $parameters = [])
    {
        if ($filter !== null) {
            $this->setFilter($filter);
        }
        $this->setParameters($parameters);
    }
    
    /**
     * Returns filter.
     *
     * @return BuilderInterface
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * Sets filter.
     *
     * @param BuilderInterface $filter
     */
    public function setFilter(BuilderInterface $filter)
    {
        $this->filter = $filter;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'not';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $query = [];
        $query['filter'] = [$this->filter->getType() => $this->filter->toArray()];

        $output = $this->processArray($query);

        return $output;
    }
}
