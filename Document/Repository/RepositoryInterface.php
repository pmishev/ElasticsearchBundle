<?php

namespace Sineflow\ElasticsearchBundle\Document\Repository;

use Sineflow\ElasticsearchBundle\Finder\Finder;
use Sineflow\ElasticsearchBundle\Manager\IndexManager;

/**
 * Defines the interface for type repositories
 */
interface RepositoryInterface
{
    /**
     * Constructor
     *
     * @param IndexManager $manager
     * @param string       $documentClass
     * @param Finder       $finder
     */
    public function __construct($manager, $documentClass, Finder $finder);

}
