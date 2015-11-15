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
     * @param string $documentClass The short path to the type entity (e.g AppBundle:MyType)
     * @param string $providerId
     */
    public function addProvider($documentClass, $providerId)
    {
        $this->providers[$documentClass] = $providerId;
    }

    /**
     * Gets the provider for a type.
     *
     * @param string $documentClass The short path to the type entity (e.g AppBundle:MyType)
     * @return ProviderInterface
     * @throws \InvalidArgumentException if no provider was registered for the type
     */
    public function getProviderInstance($documentClass)
    {
        if (isset($this->providers[$documentClass])) {
            return $this->container->get($this->providers[$documentClass]);
        }

        // Return default self-provider, if no specific one was registered
        $providerClass = $this->container->getParameter('sfes.provider_self.class');
        if (class_exists($providerClass)) {
            $indexManager = $this->container->get('sfes.index_manager_registry')->get(
                $this->container->get('sfes.document_metadata_collector')->getDocumentClassIndex($documentClass)
            );

            return new $providerClass(
                $documentClass,
                $this->container->get('sfes.document_metadata_collector'),
                $indexManager,
                $documentClass
            );
        }

        throw new \InvalidArgumentException(sprintf('No provider is registered for type "%s".', $documentClass));
    }
}
