<?php

namespace Sineflow\ElasticsearchBundle\DependencyInjection\Compiler;

use Sineflow\ElasticsearchBundle\Mapping\DocumentMetadataCollector;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiles elastic search data.
 */
class MappingPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $indices = $container->getParameter('sfes.indices');

        // Go through each defined index and register a manager service for each
        foreach ($indices as $indexManagerName => $indexSettings) {
            // Skip abstract index definitions, as they are only used as templates for real ones
            if (isset($indexSettings['abstract']) && true === $indexSettings['abstract']) {
                continue;
            }

            // Make sure the connection service definition exists
            $connectionService = sprintf('sfes.connection.%s', $indexSettings['connection']);
            if (!$container->hasDefinition($connectionService)) {
                throw new InvalidConfigurationException(
                    'There is no ES connection with name ' . $indexSettings['connection']
                );
            }

            $indexManagerDefinition = new Definition(
                'Sineflow\ElasticsearchBundle\Manager\IndexManager',
                [
                    $indexManagerName,
                    $container->getDefinition($connectionService),
                    $container->getDefinition('sfes.document_metadata_collection'),
                    $container->getDefinition('sfes.provider_registry'),
                    $container->getDefinition('sfes.finder'),
                    $this->getIndexParams($indexSettings, $container),
                ]
            );

            $indexManagerDefinition->addMethodCall('setUseAliases', [$indexSettings['use_aliases']]);

//            $this->setWarmers($connection, $settings['connection'], $container);

            $container->setDefinition(
                sprintf('sfes.index.%s', strtolower($indexManagerName)),
                $indexManagerDefinition
            );
        }
    }

    /**
     * Returns params for index.
     *
     * @param array            $indexSettings
     * @param ContainerBuilder $container
     *
     * @return array
     */
    private function getIndexParams(array $indexSettings, ContainerBuilder $container)
    {
        $index = ['index' => $indexSettings['name']];

        if (!empty($indexSettings['settings'])) {
            $index['body']['settings'] = $indexSettings['settings'];
        }

        $mappings = [];
        /** @var DocumentMetadataCollector $metadataCollector */
        $metadataCollector = $container->get('sfes.document_metadata_collector');
//        $paths = [];

        $documentClassNames = $indexSettings['types'];

        foreach ($documentClassNames as $documentClassName) {
            $mappings = array_replace_recursive(
                $mappings,
                $metadataCollector->getClientMapping($documentClassName)
            );
//            $paths = array_replace($paths, $metadataCollector->getProxyPaths());
        }

//        if ($container->hasParameter('es.proxy_paths')) {
//            $paths = array_replace($paths, $container->getParameter('es.proxy_paths'));
//        }
//        $container->setParameter('es.proxy_paths', $paths);

        if (!empty($mappings)) {
            $index['body']['mappings'] = $mappings;
        }

        return $index;
    }

//    /**
//     * Returns warmers for client.
//     *
//     * @param Definition       $connectionDefinition
//     * @param string           $connection
//     * @param ContainerBuilder $container
//     *
//     * @return array
//     *
//     * @throws \LogicException If connection is not found.
//     *
//     * TODO: warmers should be set to an index
//     */
//    private function setWarmers($connectionDefinition, $connection, ContainerBuilder $container)
//    {
//        $warmers = [];
//        foreach ($container->findTaggedServiceIds('sfes.warmer') as $id => $tags) {
//            if (array_key_exists('manager', $tags[0])) {
//                $connections = [];
//                if (strpos($tags[0]['manager'], ',')) {
//                    $connections = explode(',', $tags[0]['manager']);
//                }
//
//                if (in_array($connection, $connections) || $tags[0]['manager'] === $connection) {
//                    $connectionDefinition->addMethodCall('addWarmer', [new Reference($id)]);
//                }
//            }
//        }
//
//        return $warmers;
//    }
}
