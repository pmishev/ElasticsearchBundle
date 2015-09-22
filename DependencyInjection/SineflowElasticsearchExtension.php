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
        $this->removeAbstractIndices($config['indices']);

        $container->setParameter('sfes.document_dir', $config['document_dir']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $container->setParameter('sfes.connections', $config['connections']);
        $container->setParameter('sfes.indices', $config['indices']);

        $this->addMetadataCollectionDefinition($config, $container);
        $this->addConnectionDefinitions($config, $container);
        $this->addDocumentsResource($config, $container);
    }

    /**
     * Remove the abstract indices, which are only user as a template, from the indices list
     *
     * @param array $indices The indices config array
     */
    private function removeAbstractIndices(array &$indices)
    {
        foreach ($indices as $indexManagerName => $indexSettings) {
            // Skip abstract index definitions, as they are only used as templates for real ones
            if (isset($indexSettings['abstract']) && true === $indexSettings['abstract']) {
                unset($indices[$indexManagerName]);
            }
        }
    }

    private function addMetadataCollectionDefinition(array $config, ContainerBuilder $container)
    {
        $documentsMetadataDefinitions = [];
        $indices = $config['indices'];

        foreach ($indices as $indexManagerName => $indexSettings) {
            $documentsMetadataDefinitions[$indexManagerName] = $this->getDocumentsMetadataDefinitions($container, $indexSettings);
        }

        $documentsMetadataCollection = new Definition(
            'Sineflow\ElasticsearchBundle\Mapping\DocumentMetadataCollection',
            [
                new Reference('sfes.document_locator'),
                $documentsMetadataDefinitions,
            ]
        );
        $container->setDefinition('sfes.document_metadata_collection', $documentsMetadataCollection);
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
                    $connectionName,
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

            // TODO: these settings don't matter when a traceObject is defined, so I need to figure out a way to use them within our custom traceObject
            $params['tracePath'] = $connectionSettings['trace_path'];
            $params['traceLevel'] = $connectionSettings['trace_level'];

            // TODO: add support for custom logger objects
//            $params['logObject'] = new Reference('sfes.logger.trace');

            // This is necessary for the data collector, so we can show debug info in the web toolbar
            $params['traceObject'] = new Reference('sfes.logger.trace');
        }

        return $params;
    }

    /**
     * Adds document directory resource.
     * This is done, so if any entity definition is changed, the cache can be rebuilt
     *
     * @param array            $config
     * @param ContainerBuilder $container
     */
    private function addDocumentsResource(array $config, ContainerBuilder $container)
    {
        $watchedBundles = [];
        foreach ($config['indices'] as $index) {
            foreach ($index['types'] as $typeEntity) {
                // Get the bundle name from the entity class short syntax (e.g. AppBundle:Product)
                $bundleName = substr($typeEntity, 0, strpos($typeEntity, ':'));
                $watchedBundles[$bundleName] = true;
            }
        }

        // Get the bundles' classes from the container registered bundles
        $watchedBundles = array_intersect_key(
            $container->getParameter('kernel.bundles'),
            $watchedBundles
        );

        foreach ($watchedBundles as $name => $class) {
            $bundle = new \ReflectionClass($class);
            $dir = dirname($bundle->getFileName()) . DIRECTORY_SEPARATOR . $config['document_dir'];
            $container->addResource(new DirectoryResource($dir));
        }
    }

}
