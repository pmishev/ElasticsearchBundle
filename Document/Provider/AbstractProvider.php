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
     * @param string                     $documentClass The type the provider is for
     * @param DocumentMetadataCollection $metadata      The metadata collection for all ES types
     */
    public function __construct($documentClass, DocumentMetadataCollection $metadata)
    {
        $this->documentMetadata = $metadata->getDocumentMetadata($documentClass);
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
     * The returned data can be either a document entity or an array ready for direct sending to ES
     *
     * @return \Generator<DocumentInterface|array>
     */
    abstract public function getDocuments();

    /**
     * Build and return a document entity from the data source
     * The returned data can be either a document entity or an array ready for direct sending to ES
     *
     * @param int|string $id
     * @return DocumentInterface|array
     */
    abstract public function getDocument($id);
}
