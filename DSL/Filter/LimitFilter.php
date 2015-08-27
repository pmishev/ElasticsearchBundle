<?php

namespace Sineflow\ElasticsearchBundle\DSL\Filter;

use Sineflow\ElasticsearchBundle\DSL\BuilderInterface;

/**
 * Represents Elasticsearch "limit" filter.
 *
 * A limit filter limits the number of documents (per shard) to execute on.
 */
class LimitFilter implements BuilderInterface
{
    /**
     * @var int
     */
    private $value;

    /**
     * @param int $value Number of documents (per shard) to execute on.
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'limit';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return [
            'value' => $this->value,
        ];
    }
}
