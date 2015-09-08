<?php

namespace Sineflow\ElasticsearchBundle\Mapping;

/**
 * Holds gathered metadata from all document entities.
 */
class DocumentMetadataCollection
{
    /**
     * <index> => [
     *      <type_class_short_name> => DocumentMetadata
     *      ...
     * ]
     * ...
     *
     * @var array
     */
    private $metadata;

    /**
     * @var DocumentLocator
     */
    private $documentLocator;

    /**
     * @var array Mappings of ES type names to their managing document classes
     */
    private $typeToClassMap = [];

    /**
     * @param DocumentLocator $documentLocator
     * @param array           $metadata
     */
    public function __construct(DocumentLocator $documentLocator, array $metadata)
    {
        $this->documentLocator = $documentLocator;
        $this->metadata = $metadata;
    }

    /**
     * Returns all document classes in the collection as keys and the corresponding index that manages them as values
     *
     * @return array
     */
    public function getDocumentClassesIndices()
    {
        $result = [];
        foreach ($this->metadata as $index => $types) {
            foreach ($types as $typeDocumentClass => $documentMetadata) {
                $result[$typeDocumentClass] = $index;
            }
        }

        return $result;
    }

    /**
     * Returns the index manager name that manages the given entity document class
     *
     * @param string $documentClass
     * @return string
     */
    public function getDocumentClassIndex($documentClass)
    {
        $indices = $this->getDocumentClassesIndices();
        if (!isset($indices[$documentClass])) {
            throw new \InvalidArgumentException(sprintf('Entity "%s" is not managed by any index manager', $documentClass));
        }

        return $indices[$documentClass];
    }

    /**
     * Returns the metadata of the documents within the specified index
     *
     * @param string $indexManagerName
     * @return DocumentMetadata[]
     * @throws \InvalidArgumentException
     */
    public function getDocumentsMetadataForIndex($indexManagerName)
    {
        if (!isset($this->metadata[$indexManagerName])) {
            throw new \InvalidArgumentException(sprintf('No metadata found for index "%s"', $indexManagerName));
        }

        $indexMetadata = $this->metadata[$indexManagerName];

        return $indexMetadata;
    }

    /**
     * Returns metadata for the specified document class short name (e.g AppBundle:Product)
     *
     * @param string $documentClass
     * @return DocumentMetadata
     */
    public function getDocumentMetadata($documentClass)
    {
        $documentClass = $this->documentLocator->getShortClassName($documentClass);
        foreach ($this->metadata as $index => $types) {
            foreach ($types as $typeDocumentClass => $documentMetadata) {
                if ($documentClass === $typeDocumentClass) {
                    return $documentMetadata;
                }
            }
        }
        throw new \InvalidArgumentException(sprintf('Metadata for type "%s" is not available', $documentClass));
    }

    /**
     * Returns mapping of an ES type name to the short class name of the document entity managing that type
     *
     * @return array
     */
    public function getTypeToClassMap()
    {
        if (empty($this->typeToClassMap)) {
            $this->typeToClassMap = $this->extractTypeToClassMap();
        }

        return $this->typeToClassMap;
    }

    /**
     * Extracts mapping of an ES type name to the short class name of the document entity managing that type
     *
     * @return array
     */
    private function extractTypeToClassMap()
    {
        $result = [];

        foreach ($this->metadata as $index => $types) {
            foreach ($types as $typeDocumentClass => $documentMetadata) {
                /** @var DocumentMetadata $documentMetadata */
                $result[$documentMetadata->getType()] = $typeDocumentClass;
            }
        }

        return $result;
    }

}
