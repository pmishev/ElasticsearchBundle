<?php

namespace Sineflow\ElasticsearchBundle\Event;

use Sineflow\ElasticsearchBundle\Manager\ConnectionManager;
use Sineflow\ElasticsearchBundle\Document\DocumentInterface;

/**
 * Event to be dispatched before and after persisting a document.
 */
class ElasticsearchPersistEvent extends AbstractElasticsearchEvent
{
    /**
     * @var DocumentInterface
     */
    protected $document;

    /**
     * @param ConnectionManager $connection
     * @param DocumentInterface $document
     */
    public function __construct(ConnectionManager $connection, DocumentInterface $document)
    {
        parent::__construct($connection);

        $this->document = $document;
    }

    /**
     * Returns document associated with the event.
     *
     * @return DocumentInterface
     */
    public function getDocument()
    {
        return $this->document;
    }
}
