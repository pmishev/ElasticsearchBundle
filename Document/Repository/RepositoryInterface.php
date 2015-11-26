<?php

namespace Sineflow\ElasticsearchBundle\Document\Repository;

use Sineflow\ElasticsearchBundle\Manager\IndexManager;
use Sineflow\ElasticsearchBundle\Finder\Finder;
use Sineflow\ElasticsearchBundle\Mapping\DocumentMetadataCollector;

/**
 * Defines the interface for type repositories
 */
interface RepositoryInterface
{
    /**
     * Constructor
     *
     * @param IndexManager              $indexManager
     * @param string                    $documentClass
     * @param Finder                    $finder
     * @param DocumentMetadataCollector $metadataCollector
     */
    public function __construct(IndexManager $indexManager, $documentClass, Finder $finder, DocumentMetadataCollector $metadataCollector);

}
