<?php

namespace Sineflow\ElasticsearchBundle\DTO;

/**
 * Class to be used as a data transport structure of mappings between physical indices + types,
 * to document names in short notation (e.g. AppBundle:Product)
 */
class TypesToDocumentClasses
{
    /**
     * <physical_index_name|*> => [
     *      <es_type> => <document_class>
     *      ...
     * ]
     * ...
     *
     * @var array
     */
    private $documentClasses = [];

    /**
     * Set the document class for a combination of a physical index name and a type
     * When all the needed types are unique across indices, `null` can be passed for $index
     *
     * @param string $index         The name of the physical index in Elasticsearch
     * @param string $type          The name of the type in Elasticsearch
     * @param string $documentClass The document class in short notation
     */
    public function set($index, $type, $documentClass)
    {
        $this->documentClasses[$index ?: '*'][$type] = $documentClass;
    }

    /**
     * Get the document class for a combination of a physical index name and a type
     *
     * @param string $index The name of the physical index in Elasticsearch
     * @param string $type  The name of the type in Elasticsearch
     * @return string
     */
    public function get($index, $type)
    {
        if (isset($this->documentClasses[$index][$type])) {
            return $this->documentClasses[$index][$type];
        } elseif (isset($this->documentClasses['*'][$type])) {
            return $this->documentClasses['*'][$type];
        } else {
            throw new \InvalidArgumentException(sprintf('Document class for type "%s" in index "%s" is not set', $type, $index));
        }
    }
}
