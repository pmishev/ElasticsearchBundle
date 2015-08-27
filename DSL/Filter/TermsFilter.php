<?php

namespace Sineflow\ElasticsearchBundle\DSL\Filter;

use Sineflow\ElasticsearchBundle\DSL\BuilderInterface;
use Sineflow\ElasticsearchBundle\DSL\ParametersTrait;

/**
 * Represents Elasticsearch "terms" filter.
 */
class TermsFilter implements BuilderInterface
{
    use ParametersTrait;

    /**
     * @var string
     */
    private $field;

    /**
     * @var array
     */
    private $terms;

    /**
     * @param string $field      Field name.
     * @param array  $terms      An array of terms.
     * @param array  $parameters Optional parameters.
     */
    public function __construct($field, $terms, array $parameters = [])
    {
        $this->field = $field;
        $this->terms = $terms;
        $this->setParameters($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'terms';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $query = [$this->field => $this->terms];

        $output = $this->processArray($query);

        return $output;
    }
}
