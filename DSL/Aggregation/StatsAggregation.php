<?php

namespace Sineflow\ElasticsearchBundle\DSL\Aggregation;

use Sineflow\ElasticsearchBundle\DSL\Aggregation\Type\MetricTrait;
use Sineflow\ElasticsearchBundle\DSL\ScriptAwareTrait;

/**
 * Class representing StatsAggregation.
 */
class StatsAggregation extends AbstractAggregation
{
    use MetricTrait;
    use ScriptAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'stats';
    }

    /**
     * {@inheritdoc}
     */
    public function getArray()
    {
        $out = [];
        if ($this->getField()) {
            $out['field'] = $this->getField();
        }
        if ($this->getScript()) {
            $out['script'] = $this->getScript();
        }

        return $out;
    }
}
