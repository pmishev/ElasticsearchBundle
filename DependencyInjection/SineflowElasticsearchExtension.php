<?php

namespace Sineflow\ElasticsearchBundle\DependencyInjection;

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

        $container->setParameter('sfes.document_dir', $config['document_dir']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $container->setParameter('sfes.connections', $config['connections']);
        $container->setParameter('sfes.indices', $config['indices']);

        $this->addDocumentsResource($config, $container);
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

        // TODO: once the metadata is no longer gathered during container compilation,
        // figure out another way of invalidating the cache when a document in the watched bundles' document dirs is changed
        // because it won't be the container cache that needs invalidation, but the metadata cache, which I guess would be separate


        foreach ($watchedBundles as $name => $class) {
            $bundle = new \ReflectionClass($class);
            $dir = dirname($bundle->getFileName()) . DIRECTORY_SEPARATOR . $config['document_dir'];
            $container->addResource(new DirectoryResource($dir));
        }
    }

}
