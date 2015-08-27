<?php

namespace Sineflow\ElasticsearchBundle\DSL\Aggregation;

use Sineflow\ElasticsearchBundle\DSL\Aggregation\Type\MetricTrait;
use Sineflow\ElasticsearchBundle\DSL\ScriptAwareTrait;

/**
 * Class representing Extended stats aggregation.
 */
class ExtendedStatsAggregation extends AbstractAggregation
{
    use MetricTrait;
    use ScriptAwareTrait;

    /**
     * @var int
     */
    private $sigma;

    /**
     * @return int
     */
    public function getSigma()
    {
        return $this->sigma;
    }

    /**
     * @param int $sigma
     */
    public function setSigma($sigma)
    {
        $this->sigma = $sigma;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'extended_stats';
    }

    /**
     * {@inheritdoc}
     */
    public function getArray()
    {
        $out = array_filter(
            [
                'field' => $this->getField(),
                'script' => $this->getScript(),
                'sigma' => $this->getSigma(),
            ],
            function ($val) {
                return ($val || is_numeric($val));
            }
        );

        return $out;
    }
}
