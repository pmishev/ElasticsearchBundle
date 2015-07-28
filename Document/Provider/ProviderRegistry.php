<?php

namespace Sineflow\ElasticsearchBundle\Document\Provider;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * References persistence providers for each index and type.
 */
class ProviderRegistry implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    private $providers = array();

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
     * Registers a provider for the specified type entity.
     *
     * @param string $type       The short path to the type entity (e.g AppBundle:MyType)
     * @param string $providerId
     */
    public function addProvider($type, $providerId)
    {
        $this->providers[$type] = $providerId;
    }

    /**
     * Gets all registered providers.
     *
     * @return ProviderInterface[]
     */
    public function getAllProviderInstances()
    {
        $providers = array();
        foreach ($this->providers as $type => $providerId) {
            $providers[$type] = $this->container->get($providerId);
        }

        return $providers;
    }

    /**
     * Gets the provider for a type.
     *
     * @param string $type
     * @return ProviderInterface
     * @throws \InvalidArgumentException if no provider was registered for the type
     */
    public function getProviderInstance($type)
    {
        if (!isset($this->providers[$type])) {
            throw new \InvalidArgumentException(sprintf('No provider was registered for type "%s".', $type));
        }

        return $this->container->get($this->providers[$type]);
    }
}
