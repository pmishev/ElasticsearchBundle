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
        $connections = $container->getParameter('sfes.connections');
        $indices = $container->getParameter('sfes.indices');

        // Go through each defined connection and register a manager service for each
        foreach ($connections as $connectionName => $connectionSettings) {
            $connectionName = strtolower($connectionName);

            $client = new Definition(
                'Elasticsearch\Client',
                [
                    $this->getClientParams($connectionSettings, $container),
                ]
            );
            $connectionDefinition = new Definition(
                'Sineflow\ElasticsearchBundle\Client\Connection',
                [
                    $client,
                    $connectionSettings,
                ]
            );

            $container->setDefinition(
                sprintf('sfes.connection.%s', $connectionName),
                $connectionDefinition
            );

            if ($connectionName === 'default') {
                $container->setAlias('sfes.connection', 'sfes.connection.default');
            }
        }

        // Go through each defined index and register a manager service for each
        foreach ($indices as $indexManagerName => $indexSettings) {
            // Skip abstract index definitions, as they are only used as templates for real ones
            if (isset($indexSettings['abstract']) && true === $indexSettings['abstract']) {
                continue;
            }

            $typesMetadata = $this->getTypesMetadata($container, $indexSettings);

            // TODO: Do we need this ClassMetadataCollection wrapper at all? Why not use $typesMetadata directly
            $typesMetadataCollection = new Definition(
                'Sineflow\ElasticsearchBundle\Mapping\ClassMetadataCollection',
                [
                    $typesMetadata,
                ]
            );

            // Make sure the connection service definition exists
            $connectionService = sprintf('sfes.connection.%s', $indexSettings['connection']);
            if (!$container->hasDefinition($connectionService)) {
                throw new InvalidConfigurationException(
                    'There is no ES connection with name ' . $indexSettings['connection']
                );
            }

            $indexManagerDefinition = new Definition(
                'Sineflow\ElasticsearchBundle\ORM\IndexManager',
                [
                    $container->findDefinition($connectionService),
                    $typesMetadataCollection,
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
     * Fetches metadata for the types within an index
     *
     * @param ContainerBuilder $container
     * @param array            $indexSettings
     *
     * @return array
     */
    private function getTypesMetadata(ContainerBuilder $container, $indexSettings)
    {
        $out = [];

        /** @var DocumentMetadataCollector $metaCollector */
        $metaCollector = $container->get('sfes.document_metadata_collector');
        foreach ($indexSettings['types'] as $typeClass) {
            foreach ($metaCollector->getMapping($typeClass) as $typeName => $metadata) {
                $metadataDefinition = new Definition('Sineflow\ElasticsearchBundle\Mapping\ClassMetadata');
                $metadataDefinition->addArgument([$typeName => $metadata]);

                if (strpos($typeClass, ':') === false) {
                    $out[$typeClass . ':' . $metadata['class']] = $metadataDefinition;
                } else {
                    $out[$typeClass] = $metadataDefinition;
                }
            }
        }

        return $out;

    }

//    /**
//     * Fetches bundles metadata for specific manager settings.
//     *
//     * @param ContainerBuilder $container
//     * @param array            $settings
//     *
//     * @return array
//     */
//    private function getBundlesMetadata(ContainerBuilder $container, $settings)
//    {
//        $out = [];
//
//        /** @var MetadataCollector $collector */
//        $collector = $container->get('sfes.metadata_collector');
//        foreach ($settings['types'] as $bundle) {
//            foreach ($collector->getMapping($bundle) as $repository => $metadata) {
//                $metadataDefinition = new Definition('Sineflow\ElasticsearchBundle\Mapping\ClassMetadata');
//                $metadataDefinition->addArgument([$repository => $metadata]);
//
//                if (strpos($bundle, ':') === false) {
//                    $out[$bundle . ':' . $metadata['class']] = $metadataDefinition;
//                } else {
//                    $out[$bundle] = $metadataDefinition;
//                }
//            }
//        }
//
//        return $out;
//    }

//    /**
//     * Builds connection definition.
//     *
//     * @param ContainerBuilder $container
//     * @param array            $connections
//     * @param array            $indexSettings
//     *
//     * @return Definition
//     *
//     * @throws InvalidConfigurationException
//     */
//    private function getConnectionDefinition(ContainerBuilder $container, $connections, $indexSettings)
//    {
//        if (!isset($connections[$indexSettings['connection']])) {
//            throw new InvalidConfigurationException(
//                'There is no ES connection with name ' . $indexSettings['connection']
//            );
//        }
//
//        $client = new Definition(
//            'Elasticsearch\Client',
//            [
//                $this->getClientParams($connections[$indexSettings['connection']], $container),
//            ]
//        );
//        $connection = new Definition(
//            'Sineflow\ElasticsearchBundle\Client\Connection',
//            [
//                $client,
//                $this->getIndexParams($connections[$indexSettings['connection']], $indexSettings, $container),
//            ]
//        );
//
//        $this->setWarmers($connection, $indexSettings['connection'], $container);
//
//        return $connection;
//    }

    /**
     * Returns params for ES client.
     *
     * @param array            $connectionSettings
     * @param ContainerBuilder $container
     *
     * @return array
     */
    private function getClientParams(array $connectionSettings, ContainerBuilder $container)
    {
        $params = ['hosts' => $connectionSettings['hosts']];

        // TODO: handle this better, maybe with OptionsResolver
        if (!empty($connectionSettings['params']['auth'])) {
            $params['connectionParams']['auth'] = array_values($connectionSettings['params']['auth']);
        }

        if ($connectionSettings['logging'] === true) {
            $params['logging'] = true;
            $params['logPath'] = $connectionSettings['log_path'];
            $params['logLevel'] = $connectionSettings['log_level'];
            $params['tracePath'] = $connectionSettings['trace_path'];
            $params['traceLevel'] = $connectionSettings['trace_level'];
            // TODO: add support for custom logger objects
//            $params['logObject'] = new Reference('es.logger.trace');
//            $params['traceObject'] = new Reference('es.logger.trace');
        }

        return $params;
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
        $paths = [];

        if (!empty($indexSettings['types'])) {
            $bundles = $indexSettings['types'];
        } else {
            $bundles = array_keys($container->getParameter('kernel.bundles'));
        }

        foreach ($bundles as $bundle) {
            $mappings = array_replace_recursive(
                $mappings,
                $metadataCollector->getClientMapping($bundle)
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
