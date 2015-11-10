<?php

namespace Sineflow\ElasticsearchBundle\Document\Repository;

use Elasticsearch\Common\Exceptions\Missing404Exception;
use Sineflow\ElasticsearchBundle\Document\DocumentInterface;
use Sineflow\ElasticsearchBundle\Finder\Finder;
use Sineflow\ElasticsearchBundle\Result\Converter;
use Sineflow\ElasticsearchBundle\Manager\IndexManager;
use Sineflow\ElasticsearchBundle\Mapping\DocumentMetadata;

/**
 * Repository class.
 */
class Repository implements RepositoryInterface
{
    /**
     * @var IndexManager
     */
    private $indexManager;

    /**
     * The document class in short notation (e.g. AppBundle:Product)
     *
     * @var string
     */
    protected $documentClass;

    /**
     * @var Finder
     */
    protected $finder;

    /**
     * The type metadata
     *
     * @var DocumentMetadata
     */
    private $metadata;

    /**
     * @var string
     */
    private $languageSeparator;

    /**
     * Constructor.
     *
     * @param IndexManager $indexManager
     * @param string       $documentClass
     * @param Finder       $finder
     * @param string       $languageSeparator
     */
    public function __construct($indexManager, $documentClass, Finder $finder, $languageSeparator)
    {
        $this->indexManager = $indexManager;
        $this->documentClass = $documentClass;
        $this->finder = $finder;
        $this->languageSeparator = $languageSeparator;

        // Get the metadata of the document class managed by the repository
        $metadata = $this->getManager()->getDocumentsMetadata([$documentClass]);
        if (empty($metadata)) {
            throw new \InvalidArgumentException(sprintf('Type "%s" is not managed by index "%s"', $documentClass, $indexManager->getManagerName()));
        }
        $this->metadata = $metadata[$documentClass];
    }

    /**
     * Returns elasticsearch manager used in the repository.
     *
     * @return IndexManager
     */
    public function getManager()
    {
        return $this->indexManager;
    }

    /**
     * Returns a single document data by ID or null if document is not found.
     *
     * @param string $id         Document Id to find.
     * @param int    $resultType Result type returned.
     *
     * @return DocumentInterface|null
     */
    public function getById($id, $resultType = Finder::RESULTS_OBJECT)
    {
        return $this->finder->get($this->metadata->getShortClassName(), $id, $resultType);
    }

    /**
     * Removes a single document data by ID.
     *
     * @param string $id Document ID to remove.
     *
     * @return array
     *
     * @throws \LogicException
     */
    public function remove($id)
    {
        $params = [
            'index' => $this->getManager()->getWriteAlias(),
            'type' => $this->metadata->getType(),
            'id' => $id,
        ];

        $response = $this->getManager()->getConnection()->getClient()->delete($params);

        return $response;
    }

    /**
     * Reindex a single document in the ES index
     *
     * @param int $id
     */
    public function reindex($id)
    {
        $this->indexManager->reindex($this->documentClass, $id);
    }
}
