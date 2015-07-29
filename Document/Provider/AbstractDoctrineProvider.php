<?php

namespace Sineflow\ElasticsearchBundle\Document\Provider;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Sineflow\ElasticsearchBundle\Mapping\DocumentMetadataCollection;

/**
 * Base doctrine document provider
 */
abstract class AbstractDoctrineProvider extends AbstractProvider
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var Query
     */
    protected $query;

    /**
     * @var int How many records to retrieve from DB at once
     */
    protected $batchSize = 1000;

    /**
     * @var string The short class name of the document entity the provider is for
     */
    protected $documentClass = 'AppBundle:Journalist';

    /**
     * @param DocumentMetadataCollection $metadata
     * @param EntityManager              $em
     */
    public function __construct(DocumentMetadataCollection $metadata, EntityManager $em)
    {
        parent::__construct($metadata);
        $this->em = $em;
    }

    /**
     * @param int $batchSize
     */
    public function setBatchSize($batchSize)
    {
        $this->batchSize = $batchSize;
    }

    /**
     * Gets the query that will return all records from the DB
     *
     * @return Query
     */
    abstract public function getQuery();

    abstract protected function getAsDocument($record);

    /**
     * Returns a PHP Generator for iterating over the full dataset of source data that is to be inserted in ES
     *
     * @return \Generator<DocumentInterface>
     */
    public function getDocuments()
    {
        $query = $this->getQuery();

        $offset = 0;
        $query->setMaxResults($this->batchSize);
        do {
            // Get a batch of records
            $query->setFirstResult($offset);
            $records = $query->getResult();

            // Convert each to an ES entity and return it
            foreach ($records as $record) {
                $document = $this->getAsDocument($record);
                yield $document;
            }

            $offset += $this->batchSize;
        } while (!empty($records));
    }
}
