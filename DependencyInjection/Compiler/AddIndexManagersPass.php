<?php

namespace Sineflow\ElasticsearchBundle\DependencyInjection\Compiler;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Compiles elastic search data.
 */
class AddIndexManagersPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $indices = $container->getParameter('sfes.indices');

        // Go through each defined index and register a manager service for each
        foreach ($indices as $indexManagerName => $indexSettings) {
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
                    $container->getDefinition('sfes.document_metadata_collector'),
                    $container->getDefinition('sfes.provider_registry'),
                    $container->getDefinition('sfes.finder'),
                    $this->getIndexParams($indexManagerName, $indexSettings, $container),
                    $container->getParameter('sfes.mlproperty.language_separator')
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
     * @param string           $indexManagerName
     * @param array            $indexSettings
     * @param ContainerBuilder $container
     * @return array
     */
    private function getIndexParams($indexManagerName, array $indexSettings, ContainerBuilder $container)
    {
        $index = ['index' => $indexSettings['name']];

        if (!empty($indexSettings['settings'])) {
            $index['body']['settings'] = $indexSettings['settings'];
        }

        $mappings = [];

        $metadataCollection = $container->get('sfes.document_metadata_collection');
        $metadata = $metadataCollection->getDocumentsMetadataForIndex($indexManagerName);
        foreach ($metadata as $className => $documentMetadata) {
            $mappings[$documentMetadata->getType()] = $documentMetadata->getClientMapping();
        }

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
