<?php

namespace Sineflow\ElasticsearchBundle\DSL\Filter;

use Sineflow\ElasticsearchBundle\DSL\BuilderInterface;

/**
 * Represents Elasticsearch "type" filter.
 *
 * Filters documents matching the provided type.
 */
class TypeFilter implements BuilderInterface
{
    /**
     * @var string
     */
    private $type;

    /**
     * Constructor.
     *
     * @param string $type Type name.
     */
    public function __construct($type)
    {
        $this->type = $type;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'type';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return [
            'value' => $this->type,
        ];
    }
}
