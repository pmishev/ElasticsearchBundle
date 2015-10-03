<?php

namespace Sineflow\ElasticsearchBundle\DependencyInjection;

use Psr\Log\LogLevel;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * This is the class that validates and merges configuration from app/config files.
 */
class Configuration implements ConfigurationInterface
{
    private $kernelLogsDir;

    /**
     * @param string $kernelLogsDir
     */
    public function  __construct($kernelLogsDir)
    {
        $this->kernelLogsDir = $kernelLogsDir;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('sineflow_elasticsearch');

        $rootNode
            ->children()
                ->scalarNode('document_dir')
                    ->info("Sets directory name from which documents will be loaded from bundles.'Document' by default")
                    ->defaultValue('Document')
                ->end()
                ->scalarNode('bulk_batch_size')
                    ->info("The number of requests to send at once, when doing bulk operations")
                    ->defaultValue('1000')
                ->end()
                ->append($this->getConnectionsNode())
                ->append($this->getIndicesNode())
            ->end();

        return $treeBuilder;
    }

    /**
     * Connections configuration node.
     *
     * @return \Symfony\Component\Config\Definition\Builder\NodeDefinition
     *
     * @throws InvalidConfigurationException
     */
    private function getConnectionsNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('connections');

        $node
            ->isRequired()
            ->requiresAtLeastOneElement()
            ->info('Defines connections Elasticsearch clusters and their parameters.')
            ->prototype('array')
                ->children()
                    ->arrayNode('hosts')
                        ->info('Defines hosts to connect to.')
                        ->requiresAtLeastOneElement()
                        ->defaultValue(['127.0.0.1:9200'])
                        ->prototype('scalar')
                            ->beforeNormalization()
                                ->ifArray()
                                ->then(
                                    function ($value) {
                                        if (!array_key_exists('host', $value)) {
                                            throw new InvalidConfigurationException(
                                                'Host must be configured under hosts configuration tree.'
                                            );
                                        }

                                        return $value['host'];
                                    }
                                )
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('params')
                        ->info('Connection parameters for the Elasticsearch client')
                        ->children()
                            ->arrayNode('auth')
                                ->info('Holds information for http authentication.')
                                ->children()
                                    ->scalarNode('username')
                                        ->isRequired()
                                        ->example('john')
                                    ->end()
                                    ->scalarNode('password')
                                        ->isRequired()
                                        ->example('mytopsecretpassword')
                                    ->end()
                                    ->scalarNode('option')
                                        ->defaultValue('Basic')
                                        ->info('authentication type')
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->scalarNode('profiling')
                        ->defaultTrue()
                        ->info('Enable/disable profiling.')
                    ->end()
                    ->scalarNode('logging')
                        ->defaultTrue()
                        ->info('Enable/disable logging.')
                    ->end()

                ->end()
            ->end();

        return $node;
    }

    /**
     * Managers configuration node.
     *
     * @return \Symfony\Component\Config\Definition\Builder\NodeDefinition
     */
    private function getIndicesNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('indices');

        $node
            ->isRequired()
            ->requiresAtLeastOneElement()
            ->info('Defines Elasticsearch indices')
            ->prototype('array')
            ->beforeNormalization()
                ->ifTrue(function($v) {
                    return isset($v['abstract']) && true === $v['abstract'];
                })
                ->then(function($v) {
                    // Set a dummy name for abstract indices, as it is not used, but needs to pass validation
                    $v['name'] = 'undefined';
                    // Allow for an empty 'connection' for abstract indices
                    if (empty($v['connection'])) {
                        $v['connection'] = 'undefined';
                    }

                    return $v;
                })
            ->end()
                ->children()
                    ->booleanNode('abstract')
                        ->defaultFalse()
                        ->info('If true, no physical index will be associated with it. It can only be extended.')
                    ->end()
//                    ->scalarNode('extends')
//                        ->info('Allows inheritance of all settings of another index.')
//                    ->end()
                    ->scalarNode('connection')
                        ->isRequired()
                        ->cannotBeEmpty()
                        ->defaultValue('default')
                        ->info('Sets connection for index.')
                    ->end()
                    ->scalarNode('name')
                        ->info('The name of the index in Elasticsearch')
                        ->isRequired()
                        ->cannotBeEmpty()
                    ->end()
                    ->booleanNode('use_aliases')
                        ->info('If enabled, instead of a physical index <name>, A physical index <name>_YmdHisu will be created with a <name> and <name>_write aliases pointing to it')
                        ->defaultTrue()
                    ->end()
                    ->arrayNode('settings')
                        ->defaultValue([])
                        ->info('Sets index settings.')
                        ->prototype('variable')->end()
                    ->end()
                    ->arrayNode('types')
                        ->info('Defines which types will reside in this index, by specifying their entity classes, e.g AppBundle:Product')
                        ->prototype('scalar')->end()
                    ->end()
                ->end()
            ->end();

        return $node;
    }
}
