<?php

namespace Sineflow\ElasticsearchBundle\DependencyInjection;

use Sineflow\ElasticsearchBundle\Mapping\DocumentMetadataCollector;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages bundle configuration.
 */
class SineflowElasticsearchExtension extends Extension
{

    /**
     * @param array            $config
     * @param ContainerBuilder $container
     * @return Configuration
     */
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration($container->getParameter('kernel.logs_dir'));
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $container->setParameter('sfes.connections', $config['connections']);
        $container->setParameter('sfes.indices', $config['indices']);

        // TODO: uncomment when needed
        $this->addDocumentFinderDefinition($config, $container);
        $this->addMetadataCollectorDefinition($config, $container);
        $this->addMetadataCollectionDefinition($config, $container);
        $this->addConnectionDefinitions($config, $container);

//        $this->addDocumentsResource($config, $container);
//        $this->addDataCollectorDefinition($config, $container);
//
//        $this->addClassesToCompile(
//            [
//                'ONGR\ElasticsearchBundle\Mapping\Proxy\ProxyInterface',
//            ]
//        );
    }

    /**
     * Adds DocumentFinder definition to container (ID: sfes.document_finder).
     *
     * @param array            $config
     * @param ContainerBuilder $container
     */
    private function addDocumentFinderDefinition(array $config, ContainerBuilder $container)
    {
        $documentFinder = new Definition(
            'Sineflow\ElasticsearchBundle\Mapping\DocumentFinder',
            [
                $container->getParameter('kernel.bundles'),
            ]
        );
        $documentFinder->addMethodCall('setDocumentDir', [$config['document_dir']]);
        $container->setDefinition('sfes.document_finder', $documentFinder);
    }

    /**
     * Adds MetadataCollector definition to container (ID: sfes.metadata_collector).
     *
     * @param array            $config
     * @param ContainerBuilder $container
     */
    private function addMetadataCollectorDefinition(array $config, ContainerBuilder $container)
    {
        $cachedReader = new Definition(
            'Doctrine\Common\Annotations\FileCacheReader',
            [
                new Definition('Doctrine\Common\Annotations\AnnotationReader'),
                $this->getCacheDir($container, 'annotations'),
                $container->getParameter('kernel.debug'),
            ]
        );

        $documentParser = new Definition(
            'Sineflow\ElasticsearchBundle\Mapping\DocumentParser',
            [
                $cachedReader,
                new Reference('sfes.document_finder'),
            ]
        );

//        $proxyLoader = new Definition(
//            'Sineflow\ElasticsearchBundle\Mapping\Proxy\ProxyLoader',
//            [
//                $this->getCacheDir($container, 'proxies'),
//                $container->getParameter('kernel.debug'),
//            ]
//        );

//        $metadataCollector = new Definition(
//            'Sineflow\ElasticsearchBundle\Mapping\MetadataCollector',
//            [
//                new Reference('sfes.document_finder'),
//                $documentParser,
//                $proxyLoader,
//            ]
//        );
//        $container->setDefinition('sfes.metadata_collector', $metadataCollector);

        $metadataCollector = new Definition(
            'Sineflow\ElasticsearchBundle\Mapping\DocumentMetadataCollector',
            [
                new Reference('sfes.document_finder'),
                $documentParser,
            ]
        );
        $container->setDefinition('sfes.document_metadata_collector', $metadataCollector);
    }

    private function addMetadataCollectionDefinition(array $config, ContainerBuilder $container)
    {
        $documentsMetadataDefinitions = [];
        $indices = $config['indices'];

        foreach ($indices as $indexManagerName => $indexSettings) {
            // Skip abstract index definitions, as they are only used as templates for real ones
            if (isset($indexSettings['abstract']) && true === $indexSettings['abstract']) {
                continue;
            }

            $documentsMetadataDefinitions[$indexManagerName] = $this->getDocumentsMetadataDefinitions($container, $indexSettings);

            $documentsMetadataCollection = new Definition(
                'Sineflow\ElasticsearchBundle\Mapping\DocumentMetadataCollection',
                [
                    $documentsMetadataDefinitions,
                ]
            );
            $container->setDefinition('sfes.document_metadata_collection', $documentsMetadataCollection);
        }
    }

    /**
     * Fetches metadata service definitions for the types within an index
     *
     * @param ContainerBuilder $container
     * @param array            $indexSettings
     *
     * @return array
     */
    private function getDocumentsMetadataDefinitions(ContainerBuilder $container, $indexSettings)
    {
        $result = [];

        /** @var DocumentMetadataCollector $metaCollector */
        $metaCollector = $container->get('sfes.document_metadata_collector');
        foreach ($indexSettings['types'] as $typeClass) {
            foreach ($metaCollector->getMetadataFromClass($typeClass) as $typeName => $metadata) {
                $metadataDefinition = new Definition('Sineflow\ElasticsearchBundle\Mapping\DocumentMetadata');
                $metadataDefinition->addArgument([$typeName => $metadata]);
                $result[$typeClass] = $metadataDefinition;
            }
        }

        return $result;

    }

    private function addConnectionDefinitions(array $config, ContainerBuilder $container)
    {
        // Go through each defined connection and register a manager service for each
        foreach ($config['connections'] as $connectionName => $connectionSettings) {
            $connectionName = strtolower($connectionName);

            $client = new Definition(
                'Elasticsearch\Client',
                [
                    $this->getClientParams($connectionSettings, $container),
                ]
            );
            $connectionDefinition = new Definition(
                'Sineflow\ElasticsearchBundle\Manager\ConnectionManager',
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
    }

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


//
//    /**
//     * Adds document directory file resource.
//     *
//     * @param array            $config
//     * @param ContainerBuilder $container
//     */
//    private function addDocumentsResource(array $config, ContainerBuilder $container)
//    {
//        $watchedBundles = [];
//        // TODO: uncomment when needed
////        foreach ($config['managers'] as $manager) {
////            $watchedBundles = array_merge($watchedBundles, $manager['mappings']);
////        }
//
//        $watchedBundles = array_intersect_key(
//            $container->getParameter('kernel.bundles'),
//            array_flip(array_unique($watchedBundles))
//        );
//
//        foreach ($watchedBundles as $name => $class) {
//            $bundle = new \ReflectionClass($class);
//            $dir = dirname($bundle->getFileName()) . DIRECTORY_SEPARATOR . $config['document_dir'];
//            $container->addResource(new DirectoryResource($dir));
//        }
//    }
//
//    /**
//     * Adds data collector to container if debug is set to any manager.
//     *
//     * @param array            $config
//     * @param ContainerBuilder $container
//     */
//    private function addDataCollectorDefinition(array $config, ContainerBuilder $container)
//    {
//        if ($this->isDebugSet($config)) {
//            $container->setDefinition('es.logger.trace', $this->getLogTraceDefinition());
//            $container->setDefinition('es.collector', $this->getDataCollectorDefinition(['es.logger.trace']));
//        }
//    }
//
//    /**
//     * Finds out if debug is set to any manager.
//     *
//     * @param array $config
//     *
//     * @return bool
//     */
//    private function isDebugSet(array $config)
//    {
//        // TODO: uncomment when needed
////        foreach ($config['managers'] as $manager) {
////            if ($manager['debug']['enabled'] === true) {
////                return true;
////            }
////        }
//
//        return false;
//    }
//
//    /**
//     * Returns logger used for collecting data.
//     *
//     * @return Definition
//     */
//    private function getLogTraceDefinition()
//    {
//        $handler = new Definition('ONGR\ElasticsearchBundle\Logger\Handler\CollectionHandler', []);
//
//        $logger = new Definition(
//            'Monolog\Logger',
//            [
//                'tracer',
//                [$handler],
//            ]
//        );
//
//        return $logger;
//    }
//
//    /**
//     * Returns elasticsearch data collector definition.
//     *
//     * @param array $loggers
//     *
//     * @return Definition
//     */
//    private function getDataCollectorDefinition($loggers = [])
//    {
//        $collector = new Definition('ONGR\ElasticsearchBundle\DataCollector\ElasticsearchDataCollector');
//        $collector->addMethodCall('setManagers', [new Parameter('es.managers')]);
//
//        foreach ($loggers as $logger) {
//            $collector->addMethodCall('addLogger', [new Reference($logger)]);
//        }
//
//        $collector->addTag(
//            'data_collector',
//            [
//                'template' => 'ONGRElasticsearchBundle:Profiler:profiler.html.twig',
//                'id' => 'es',
//            ]
//        );
//
//        return $collector;
//    }

    /**
     * Returns cache directory.
     *
     * @param ContainerBuilder $container
     * @param string           $dir
     *
     * @return string
     */
    private function getCacheDir(ContainerBuilder $container, $dir = '')
    {
        return $container->getParameter('kernel.cache_dir') . DIRECTORY_SEPARATOR . 'sineflow' . DIRECTORY_SEPARATOR . $dir;
    }
}
