<?php

namespace Sineflow\ElasticsearchBundle\Mapping;

/**
 * DumperInterface is the interface implemented by elasticsearch document annotations.
 */
interface DumperInterface
{
    /**
     * Dumps properties into array.
     *
     * @param array $options Options to configure dump output
     *
     * @return array
     */
    public function dump(array $options = []);
}
