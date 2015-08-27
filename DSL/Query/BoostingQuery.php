<?php

namespace Sineflow\ElasticsearchBundle\DSL\Query;

use Sineflow\ElasticsearchBundle\DSL\BuilderInterface;

/**
 * Elasticsearch boosting query class.
 */
class BoostingQuery implements BuilderInterface
{
    /**
     * @var BuilderInterface
     */
    private $positive;

    /**
     * @var BuilderInterface
     */
    private $negative;

    /**
     * @var int|float
     */
    private $negativeBoost;

    /**
     * @param BuilderInterface $positive
     * @param BuilderInterface $negative
     * @param int|float        $negativeBoost
     */
    public function __construct(BuilderInterface $positive, BuilderInterface $negative, $negativeBoost)
    {
        $this->positive = $positive;
        $this->negative = $negative;
        $this->negativeBoost = $negativeBoost;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'boosting';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $query = [
            'positive' => [$this->positive->getType() => $this->positive->toArray()],
            'negative' => [$this->negative->getType() => $this->negative->toArray()],
            'negative_boost' => $this->negativeBoost,
        ];

        return $query;
    }
}
