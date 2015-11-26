<?php

namespace Sineflow\ElasticsearchBundle\Manager;

use Sineflow\ElasticsearchBundle\Document\DocumentInterface;
use Sineflow\ElasticsearchBundle\Mapping\DocumentMetadataCollector;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class to get defined index manager services
 */
class IndexManagerRegistry implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var DocumentMetadataCollector
     */
    private $metadataCollector;

    /**
     * Constructor
     *
     * @param DocumentMetadataCollector $metadataCollector
     */
    public function __construct(DocumentMetadataCollector $metadataCollector)
    {
        $this->metadataCollector = $metadataCollector;
    }

    /**
     * Sets the Container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Returns the index manager service for a given index manager name
     *
     * @param string $name
     * @return IndexManager
     */
    public function get($name)
    {
        $serviceName = sprintf('sfes.index.%s', $name);
        if (!$this->container->has($serviceName)) {
            throw new \RuntimeException(sprintf('No manager is defined for "%s" index', $name));
        }

        return $this->container->get($serviceName);
    }

    /**
     * Returns the index manager managing a given Elasticsearch entity
     *
     * @param DocumentInterface $entity
     * @return IndexManager
     */
    public function getByEntity(DocumentInterface $entity)
    {
        $indexManagerName = $this->metadataCollector->getDocumentClassIndex(get_class($entity));

        return $this->get($indexManagerName);
    }
}
