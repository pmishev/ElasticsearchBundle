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

     * @var array
     */
    private $metadata;

    /**
     * @var DocumentFinder
     */
    private $finder;

    /**
     * @var array Mappings of ES type names to their managing document classes
     */
    private $typeToClassMap = [];

    /**
     * @param DocumentFinder $finder
     * @param array          $metadata
     */
    public function __construct(DocumentFinder $finder, array $metadata)
    {
        $this->finder = $finder;
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
     * Returns the document classes names (in short notation, i.e. AppBundle:Product) for an index
     *
     * @param string $indexManagerName
     * @return array
     * @throws \HttpInvalidParamException
     *
     * TODO: maybe rename this to getMetadataForIndex
     */
    public function getDocumentClassesForIndex($indexManagerName)
    {
        if (!isset($this->metadata[$indexManagerName])) {
            throw new \HttpInvalidParamException(sprintf('No metadata found for index "%s"', $indexManagerName));
        }

        $indexMetadata = $this->metadata[$indexManagerName];
        $documentClasses = array_keys($indexMetadata);

        return $documentClasses;
    }

    /**
     * Returns metadata for the specified document class short name (e.g AppBundle:Product)
     *
     * @param string $documentClass
     * @return DocumentMetadata
     */
    public function getDocumentMetadata($documentClass)
    {
        $documentClass = $this->finder->getShortClassName($documentClass);
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
                $result[$documentMetadata->getType()] = $typeDocumentClass;
            }
        }

        return $result;
    }

// TODO: Review the necessity of methods below

//    /**
//     * Return a list of type namespaces
//     *
//     * @return array
//     */
//    public function getTypes()
//    {
//        return array_keys($this->metadata);
//    }



    /**
     * Returns metadata.
     *
     * @param array $repositories
     *
     * @return DocumentMetadata[]
     *
     * TODO: $repositories should be renamed to $documentClasses
     * TODO: now metadata is first indexed by indices
     */
    public function getMetadata($repositories = [])
    {
        if (!empty($repositories)) {
            return array_intersect_key($this->metadata, array_flip($repositories));
        }

        return $this->metadata;
    }
}
