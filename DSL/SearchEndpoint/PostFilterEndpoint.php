<?php

namespace Sineflow\ElasticsearchBundle\DSL\SearchEndpoint;

use Sineflow\ElasticsearchBundle\DSL\Filter\PostFilter;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Search post filter dsl endpoint.
 */
class PostFilterEndpoint extends FilterEndpoint
{
    /**
     * {@inheritdoc}
     */
    public function normalize(NormalizerInterface $normalizer, $format = null, array $context = [])
    {
        if ($this->getBuilder()) {
            $postFilter = new PostFilter();
            !$this->isBool() ? : $this->getBuilder()->setParameters($this->getParameters());
            $postFilter->setFilter($this->getBuilder());

            return $postFilter->toArray();
        }

        return null;
    }
}
