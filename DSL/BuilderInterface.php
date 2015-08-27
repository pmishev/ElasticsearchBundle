<?php

namespace Sineflow\ElasticsearchBundle\DSL;

/**
 * Interface BuilderInterface.
 */
interface BuilderInterface
{
    /**
     * Generates array which will be passed to elasticsearch-php client.
     *
     * @return array|object
     */
    public function toArray();

    /**
     * Returns element type.
     *
     * @return string
     */
    public function getType();
}
