<?php

namespace Sineflow\ElasticsearchBundle\Document\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Sineflow\ElasticsearchBundle\Mapping\DocumentMetadataCollection;

/**
 * Base doctrine document provider
 */
abstract class AbstractMySqlProvider extends AbstractProvider
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
     * @var string The Doctrine entity name
     */
    protected $doctrineEntityName;

    /**
     * @param string                     $documentClass The type the provider is for
     * @param DocumentMetadataCollection $metadata      The metadata collection for all ES types
     * @param EntityManager              $em            The Doctrine entity manager
     */
    public function __construct($documentClass, DocumentMetadataCollection $metadata, EntityManager $em)
    {
        parent::__construct($documentClass, $metadata);
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
     * @return string
     */
    abstract public function getQuery();

    /**
     * Gets the params for the query
     *
     * @return array
     */
    abstract public function getParams();

    /**
     * @param mixed $entity A doctrine entity object or data array
     * @return mixed An ES document entity object or document array
     */
    abstract protected function getAsDocument($entity);

    /**
     * Returns a PHP Generator for iterating over the full dataset of source data that is to be inserted in ES
     * The returned data can be either a document entity or an array ready for direct sending to ES
     *
     * @return \Generator<DocumentInterface|array>
     */
    public function getDocuments()
    {
        set_time_limit(3600);

        $sql = $this->getQuery();
        $params = $this->getParams();

        $stmt = $this->em->getConnection()->prepare($sql);
        $stmt->execute($params);





        $offset = 0;
        $query->setMaxResults($this->batchSize);
        do {
            // Get a batch of records
            $query->setFirstResult($offset);

            $records = $stmt->fetchAll();

            // Convert each to an ES entity and return it
            foreach ($records as $record) {
                $document = $this->getAsDocument($record);

                yield $document;
            }

            $offset += $this->batchSize;
        } while (!empty($records));
    }

    /**
     * Build and return a document entity from the data source
     * The returned data can be either a document entity or an array ready for direct sending to ES
     *
     * @param int|string $id
     * @return DocumentInterface|array
     */
    public function getDocument($id)
    {
        $entity = $this->em->getRepository($this->doctrineEntityName)->find($id);

        return $this->getAsDocument($entity);
    }

}
