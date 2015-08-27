<?php

namespace ONGR\ElasticsearchBundle\DSL\Filter;

use Sineflow\ElasticsearchBundle\DSL\BuilderInterface;
use Sineflow\ElasticsearchBundle\DSL\ParametersTrait;

/**
 * Represents Elasticsearch "term" filter.
 */
class TermFilter implements BuilderInterface
{
    use ParametersTrait;

    /**
     * @var string
     */
    private $field;

    /**
     * @var string
     */
    private $term;

    /**
     * @param string $field      Field name.
     * @param string $term       Field value.
     * @param array  $parameters Optional parameters.
     */
    public function __construct($field, $term, array $parameters = [])
    {
        $this->field = $field;
        $this->term = $term;
        $this->setParameters($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'term';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $query = [$this->field => $this->term];

        $output = $this->processArray($query);

        return $output;
    }
}
