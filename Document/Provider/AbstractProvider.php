<?php

namespace Sineflow\ElasticsearchBundle\Document\Provider;

use Sineflow\ElasticsearchBundle\Mapping\DocumentMetadata;
use Sineflow\ElasticsearchBundle\Mapping\DocumentMetadataCollection;

/**
 * Base document provider
 */
abstract class AbstractProvider implements ProviderInterface
{
    /**
     * @var DocumentMetadata The metadata for the ES type the provider is for
     */
    private $documentMetadata;

    /**
     * @var string The short class name of the document entity the provider is for
     */
    protected $documentClass;

    /**
     * @param DocumentMetadataCollection $metadata
     */
    public function __construct(DocumentMetadataCollection $metadata)
    {
        $this->documentMetadata = $metadata->getDocumentMetadata($this->documentClass);
    }

    /**
     * @return DocumentMetadata
     */
    protected function getDocumentMetadata()
    {
        return $this->documentMetadata;
    }

    /**
     * Returns a PHP Generator for iterating over the full dataset of source data that is to be inserted in ES
     *
     * @return \Generator<DocumentInterface>
     */
    abstract public function getDocuments();

    /**
     * Build and return a document entity from the data source, ready for insertion into ES
     *
     * @param int|string $id
     * @return DocumentInterface
     */
    abstract public function getDocument($id);
}
