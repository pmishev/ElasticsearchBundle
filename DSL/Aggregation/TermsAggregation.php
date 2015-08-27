<?php

namespace Sineflow\ElasticsearchBundle\DSL\Aggregation;

use Sineflow\ElasticsearchBundle\DSL\Aggregation\Type\BucketingTrait;
use Sineflow\ElasticsearchBundle\DSL\ScriptAwareTrait;

/**
 * Class representing TermsAggregation.
 */
class TermsAggregation extends AbstractAggregation
{
    use BucketingTrait;
    use ScriptAwareTrait;

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
    public function getArray()
    {
        $data = array_filter(
            [
                'field' => $this->getField(),
                'script' => $this->getScript(),
            ]
        );

        return $data;
    }
}
