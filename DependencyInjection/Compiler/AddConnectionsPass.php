<?php

namespace Sineflow\ElasticsearchBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers connection service definitions
 */
class AddConnectionsPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $connections = $container->getParameter('sfes.connections');

        // Go through each defined connection and register a manager service for each
        foreach ($connections as $connectionName => $connectionSettings) {
            $connectionName = strtolower($connectionName);

            $client = new Definition(
                'Elasticsearch\Client',
                [
                    $this->getClientParams($connectionSettings),
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
     *
     * @return array
     */
    private function getClientParams(array $connectionSettings)
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

}
