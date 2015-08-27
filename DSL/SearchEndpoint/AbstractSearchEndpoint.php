<?php

namespace Sineflow\ElasticsearchBundle\DSL\SearchEndpoint;

use Sineflow\ElasticsearchBundle\Serializer\Normalizer\AbstractNormalizable;

/**
 * Abstract class used to define search endpoint with references.
 */
abstract class AbstractSearchEndpoint extends AbstractNormalizable implements SearchEndpointInterface
{
}
