<?php

namespace Sineflow\ElasticsearchBundle\Mapping\Proxy;

/**
 * Defines necessary methods that proxy documents should have.
 */
interface ProxyInterface
{
    /**
     * Should return if document exists on client index or not.
     *
     * @return bool
     */
    public function __isInitialized();
}
