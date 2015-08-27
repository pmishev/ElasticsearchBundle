<?php

namespace Sineflow\ElasticsearchBundle\DSL;

/**
 * Interface used by builders with names.
 */
interface NamedBuilderInterface extends BuilderInterface
{
    /**
     * Returns builder name.
     *
     * @return string
     */
    public function getName();
}
