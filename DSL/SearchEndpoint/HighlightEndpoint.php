<?php

namespace Sineflow\ElasticsearchBundle\DSL\SearchEndpoint;

use Sineflow\ElasticsearchBundle\DSL\BuilderInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Search highlight dsl endpoint.
 */
class HighlightEndpoint implements SearchEndpointInterface
{
    /**
     * @var BuilderInterface
     */
    private $highlight;

    /**
     * {@inheritdoc}
     */
    public function normalize(NormalizerInterface $normalizer, $format = null, array $context = [])
    {
        if ($this->getBuilder()) {
            return $this->getBuilder()->toArray();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addBuilder(BuilderInterface $builder, $parameters = [])
    {
        $this->highlight = $builder;
    }

    /**
     * {@inheritdoc}
     */
    public function getBuilder()
    {
        return $this->highlight;
    }
}
