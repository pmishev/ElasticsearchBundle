<?php

namespace Sineflow\ElasticsearchBundle\DTO;

use Sineflow\ElasticsearchBundle\Mapping\DocumentMetadata;

/**
 * Class IndicesAndTypesMetadataCollection
 *
 * To be used as a data transport structure of mappings between physical indices + types, to document metadata
 */
class IndicesAndTypesMetadataCollection
{
    /**
     * <physical_index_name|*> => [
     *      <es_type> => DocumentMetadata
     *      ...
     * ]
     * ...
     *
     * @var array
     */
    private $metadata = [];

    /**
     * Sets the metadata for a combination of a real/physical index name and a type
     *
     * @param DocumentMetadata $documentMetadata
     * @param string           $realIndex        The physical index name
     */
    public function setTypeMetadata(DocumentMetadata $documentMetadata, $realIndex = null)
    {
        $this->metadata[$realIndex ?: '*'][$documentMetadata->getType()] = $documentMetadata;
    }

    /**
     * Returns the metadata for a combination of a physical index name and a type
     *
     * @param string $type      The Elasticsearch type
     * @param string $realIndex The physical index name
     * @return DocumentMetadata
     */
    public function getTypeMetadata($type, $realIndex)
    {
        if (isset($this->metadata[$realIndex][$type])) {
            return $this->metadata[$realIndex][$type];
        } elseif (isset($this->metadata['*'][$type])) {
            return $this->metadata['*'][$type];
        } else {
            throw new \InvalidArgumentException(sprintf('No metadata available for type "%s" in index "%s"', $type, $realIndex));
        }
    }
}
