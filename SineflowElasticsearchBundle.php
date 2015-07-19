<?php

namespace Sineflow\ElasticsearchBundle;

use Sineflow\ElasticsearchBundle\DependencyInjection\Compiler\MappingPass;
use Symfony\Component\ClassLoader\MapClassLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Sineflow Elasticsearch bundle system file required by kernel.
 */
class SineflowElasticsearchBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new MappingPass());
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
//        if ($this->container->hasParameter('es.proxy_paths')) {
//            $loader = new MapClassLoader($this->container->getParameter('es.proxy_paths'));
//            $loader->register();
//        }
    }
}
