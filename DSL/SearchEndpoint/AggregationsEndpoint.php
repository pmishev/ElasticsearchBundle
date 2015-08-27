<?php

namespace Sineflow\ElasticsearchBundle\DSL\SearchEndpoint;

use Sineflow\ElasticsearchBundle\DSL\BuilderInterface;
use Sineflow\ElasticsearchBundle\DSL\NamedBuilderBag;
use Sineflow\ElasticsearchBundle\DSL\NamedBuilderInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Search aggregations dsl endpoint.
 */
class AggregationsEndpoint implements SearchEndpointInterface
{
    /**
     * @var NamedBuilderBag
     */
    private $bag;

    /**
     * Initialized aggregations bag.
     */
    public function __construct()
    {
        $this->bag = new NamedBuilderBag();
    }

    /**
     * {@inheritdoc}
     */
    public function normalize(NormalizerInterface $normalizer, $format = null, array $context = [])
    {
        if (count($this->bag->all()) > 0) {
            return $this->bag->toArray();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addBuilder(BuilderInterface $builder, $parameters = [])
    {
        if ($builder instanceof NamedBuilderInterface) {
            $this->bag->add($builder);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getBuilder()
    {
        return $this->bag->all();
    }
}
