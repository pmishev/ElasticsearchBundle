<?php

namespace Sineflow\ElasticsearchBundle\Event;

use Sineflow\ElasticsearchBundle\Manager\ConnectionManager;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event to be dispatched in various Elasticsearch methods.
 */
abstract class AbstractElasticsearchEvent extends Event
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @param ConnectionManager $connection
     */
    public function __construct(ConnectionManager $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Returns connection associated with the event.
     *
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }
}
