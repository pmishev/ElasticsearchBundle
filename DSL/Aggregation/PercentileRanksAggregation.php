<?php

namespace Sineflow\ElasticsearchBundle\DSL\Aggregation;

use Sineflow\ElasticsearchBundle\DSL\Aggregation\Type\MetricTrait;
use Sineflow\ElasticsearchBundle\DSL\ScriptAwareTrait;

/**
 * Class representing Percentile Ranks Aggregation.
 */
class PercentileRanksAggregation extends AbstractAggregation
{
    use MetricTrait;
    use ScriptAwareTrait;

    /**
     * @var array
     */
    private $values;

    /**
     * @var int
     */
    private $compression;

    /**
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * @param array $values
     */
    public function setValues($values)
    {
        $this->values = $values;
    }

    /**
     * @return int
     */
    public function getCompression()
    {
        return $this->compression;
    }

    /**
     * @param int $compression
     */
    public function setCompression($compression)
    {
        $this->compression = $compression;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'percentile_ranks';
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
                'values' => $this->getValues(),
                'compression' => $this->getCompression(),
            ],
            function ($val) {
                return ($val || is_numeric($val));
            }
        );

        $this->isRequiredParametersSet($out);

        return $out;
    }

    /**
     * @param array $a
     *
     * @return bool
     *
     * @throws \LogicException
     */
    private function isRequiredParametersSet($a)
    {
        if (array_key_exists('field', $a) && array_key_exists('values', $a)
            || (array_key_exists('script', $a) && array_key_exists('values', $a))
        ) {
            return true;
        }
        throw new \LogicException('Percentile ranks aggregation must have field and values or script and values set.');
    }
}
