<?php

namespace Sineflow\ElasticsearchBundle\DSL\Filter;

use Sineflow\ElasticsearchBundle\DSL\BuilderInterface;

/**
 * Represents Elasticsearch "exists" filter.
 *
 * @link http://www.elasticsearch.org/guide/en/elasticsearch/reference/current/query-dsl-exists-filter.html
 */
class ExistsFilter implements BuilderInterface
{
    /**
     * @var string
     */
    private $field;

    /**
     * @param string $field Field value.
     */
    public function __construct($field)
    {
        $this->field = $field;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'exists';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return [
            'field' => $this->field,
        ];
    }
}
