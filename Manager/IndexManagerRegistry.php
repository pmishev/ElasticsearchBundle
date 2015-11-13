<?php

namespace Sineflow\ElasticsearchBundle\Manager;

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
}