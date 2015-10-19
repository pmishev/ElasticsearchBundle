<?php

namespace Sineflow\ElasticsearchBundle\Manager;

use Elasticsearch\ClientBuilder;
use Psr\Log\LoggerInterface;

/**
 * Elasticsearch connection factory class
 */
class ConnectionManagerFactory
{

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var LoggerInterface
     */
    private $tracer;

    /**
     * @param LoggerInterface $tracer
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $tracer = null, LoggerInterface $logger = null)
    {
        $this->tracer = $tracer;
        $this->logger = $logger;
    }

    /**
     * @param string $connectionName
     * @param array  $connectionSettings
     * @return ConnectionManager
     */
    public function createConnectionManager($connectionName, $connectionSettings)
    {
        $clientBuilder = ClientBuilder::create();

        $clientBuilder->setHosts($connectionSettings['hosts']);

        if ($this->tracer && $connectionSettings['profiling']) {
            $clientBuilder->setTracer($this->tracer);
        }

        if ($this->logger && $connectionSettings['logging']) {
            $clientBuilder->setLogger($this->logger);
        }

        $connectionManager = new ConnectionManager(
            $connectionName,
            $clientBuilder->build(),
            $connectionSettings
        );

        $connectionManager->setLogger($this->logger);

        return $connectionManager;
    }
}
