<?php

namespace Sineflow\ElasticsearchBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Registers data providers for type entities
 */
class RegisterDataProvidersPass implements CompilerPassInterface
{
    /**
     * Mapping of class names to booleans indicating whether the class
     * implements ProviderInterface.
     *
     * @var array
     */
    private $implementations = array();

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('sfes.provider_registry')) {
            return;
        }

        $registry = $container->getDefinition('sfes.provider_registry');
        $metadataCollection = $container->get('sfes.document_metadata_collection');
        $providers = $container->findTaggedServiceIds('sfes.provider');

        // Get all types and their corresponding indices
        $typeIndices = $metadataCollection->getDocumentClassesIndices();

        foreach ($providers as $providerId => $tags) {
            $documentClass = null;
            $class = $container->getDefinition($providerId)->getClass();

            if (!$class || !$this->isProviderImplementation($class)) {
                throw new \InvalidArgumentException(sprintf('Elasticsearch provider "%s" with class "%s" must implement ProviderInterface.', $providerId, $class));
            }

            foreach ($tags as $tag) {
                if (!isset($tag['type'])) {
                    throw new \InvalidArgumentException(sprintf('Elasticsearch provider "%s" must specify the "type" attribute.', $providerId));
                }

                $documentClass = $tag['type'];

                unset($typeIndices[$documentClass]);
            }

            $registry->addMethodCall('addProvider', array($documentClass, $providerId));
        }

        // Set Elasticsearch self-provider by default for all types that do not have a provider registered
        foreach ($typeIndices as $documentClass => $index) {
            $providerDefinition = new Definition(
                'Sineflow\ElasticsearchBundle\Document\Provider\ElasticsearchProvider',
                [
                    $documentClass,
                    $container->getDefinition('sfes.document_metadata_collector'),
                    $container->getDefinition(sprintf('sfes.index.%s', $index)),
                    $documentClass
                ]
            );
            $definitionId = sprintf('sfes.provider.%s.%s', strtolower($index), strtolower($metadataCollection->getDocumentMetadata($documentClass)->getType()));
            $container->setDefinition(
                $definitionId,
                $providerDefinition
            );
            $registry->addMethodCall('addProvider', array($documentClass, $definitionId));
        }
    }

    /**
     * Returns whether the class implements ProviderInterface.
     *
     * @param string $class
     *
     * @return boolean
     */
    private function isProviderImplementation($class)
    {
        if (!isset($this->implementations[$class])) {
            $reflectionClass = new \ReflectionClass($class);
            $this->implementations[$class] = $reflectionClass->implementsInterface('Sineflow\ElasticsearchBundle\Document\Provider\ProviderInterface');
        }

        return $this->implementations[$class];
    }
}
