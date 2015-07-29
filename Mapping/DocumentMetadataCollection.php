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
     * @var array
     */
    private $typesMap = [];

    /**
     * @param array $metadata
     */
    public function __construct(array $metadata)
    {
        $this->metadata = $metadata;
    }

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
     * Returns the document classes names for an index
     *
     * @param string $indexManagerName
     * @return array
     * @throws \HttpInvalidParamException
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
     * Returns type map.
     *
     * @return array
     */
    public function getTypesMap()
    {
        if (empty($this->typesMap)) {
            $this->typesMap = $this->extractTypeMap();
        }

        return $this->typesMap;
    }

    /**
     * Returns metadata.
     *
     * @param array $repositories
     *
     * @return DocumentMetadata[]
     *
     * TODO: $repositories should be renamed to $documentClasses
     */
    public function getMetadata($repositories = [])
    {
        if (!empty($repositories)) {
            return array_intersect_key($this->metadata, array_flip($repositories));
        }

        return $this->metadata;
    }

    /**
     * Returns metadata for the specified document class short name (e.g AppBundle:Product)
     *
     * @param string $documentClass
     * @return DocumentMetadata
     */
    public function getDocumentMetadata($documentClass)
    {
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
     * Extracts type map from metadata.
     *
     * @return array
     */
    private function extractTypeMap()
    {
        $out = [];

        foreach ($this->metadata as $repository => $data) {
            $out[$data->getType()] = $repository;
        }

        return $out;
    }
}
