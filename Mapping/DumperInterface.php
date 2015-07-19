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
     * @param array $exclude Properties array to exclude from dump.
     *
     * @return array
     */
    public function dump(array $exclude = []);
}
