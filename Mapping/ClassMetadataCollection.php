<?php

namespace Sineflow\ElasticsearchBundle\Mapping;

/**
 * Holds gathered metadata from all document entities.
 */
class ClassMetadataCollection
{
    /**
     * @var ClassMetadata[]
     */
    private $metadata;

    /**
     * @var array
     */
    private $typesMap = [];

    /**
     * @param ClassMetadata[] $metadata
     */
    public function __construct(array $metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * Return a list of type namespaces
     *
     * @return array
     */
    public function getTypes()
    {
        return array_keys($this->metadata);
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
     * @return ClassMetadata[]
     */
    public function getMetadata($repositories = [])
    {
        if (!empty($repositories)) {
            return array_intersect_key($this->metadata, array_flip($repositories));
        }

        return $this->metadata;
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
