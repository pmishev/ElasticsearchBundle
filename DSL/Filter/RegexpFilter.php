<?php

namespace Sineflow\ElasticsearchBundle\DSL\Filter;

use Sineflow\ElasticsearchBundle\DSL\BuilderInterface;
use Sineflow\ElasticsearchBundle\DSL\ParametersTrait;

/**
 * Represents Elasticsearch "regexp" filter.
 */
class RegexpFilter implements BuilderInterface
{
    use ParametersTrait;

    /**
     * @var string
     */
    private $field;

    /**
     * @var string
     */
    private $regexp;

    /**
     * @param string $field      Field name.
     * @param string $regexp     Regular expression.
     * @param array  $parameters Optional parameters.
     */
    public function __construct($field, $regexp, array $parameters = [])
    {
        $this->field = $field;
        $this->regexp = $regexp;
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
            'value' => $this->regexp,
        ];

        if ($this->hasParameter('flags')) {
            $query['flags'] = $this->getParameter('flags');
            unset($this->parameters['flags']);
        }

        $output = $this->processArray([$this->field => $query]);

        return $output;
    }
}
