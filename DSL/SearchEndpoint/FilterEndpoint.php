<?php

namespace Sineflow\ElasticsearchBundle\DSL\SearchEndpoint;

use Sineflow\ElasticsearchBundle\DSL\Filter\BoolFilter;
use Sineflow\ElasticsearchBundle\DSL\Query\FilteredQuery;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Search filter dsl endpoint.
 */
class FilterEndpoint extends QueryEndpoint
{
    /**
     * {@inheritdoc}
     */
    public function normalize(NormalizerInterface $normalizer, $format = null, array $context = [])
    {
        if ($this->getBuilder()) {
            $query = new FilteredQuery();
            !$this->isBool() ? : $this->getBuilder()->setParameters($this->getParameters());
            $query->setFilter($this->getBuilder());
            $this->addReference('filtered_query', $query);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    protected function getBoolInstance()
    {
        return new BoolFilter();
    }
}
