<?php

namespace Sineflow\ElasticsearchBundle\Mapping;

use Sineflow\ElasticsearchBundle\Document\DocumentInterface;

/**
 * DocumentParser wrapper for getting type/document mapping.
 */
class DocumentMetadataCollector
{
    /**
     * @var DocumentFinder
     */
    private $finder;

    /**
     * @var DocumentParser
     */
    private $parser;

    /**
     * @var array Contains client mappings gathered from document definitions.
     */
    private $mappings = [];

    /**
     * @param DocumentFinder $finder For finding documents.
     * @param DocumentParser $parser For reading document annotations.
     */
    public function __construct($finder, $parser)
    {
        $this->finder = $finder;
        $this->parser = $parser;
    }

    /**
     * Retrieves type mapping for the Elasticsearch client
     *
     * @param string $documentClassName Bundle name to retrieve mappings from.
     * @param bool   $useCache          Whether to use cached mapping or rescan document annotations.
     *
     * TODO: This method should be moved to the DocumentMetadata class
     *
     * @return array
     */
    public function getClientMapping($documentClassName, $useCache = true)
    {
        if ($useCache && isset($this->mappings[$documentClassName])) {
            return $this->mappings[$documentClassName];
        }

        $mappings = [];
        foreach ($this->getMetadataFromClass($documentClassName) as $type => $metadata) {
            if (!empty($metadata['properties'])) {
                $mappings[$type] = array_filter(
                    array_merge(
                        ['properties' => $metadata['properties']],
                        $metadata['fields']
                    ),
                    function ($value) {
                        // Remove all empty non-boolean values from the mapping array
                        return (bool) $value || is_bool($value);
                    }
                );
            }
        }

        $this->mappings[$documentClassName] = $mappings;

        return $this->mappings[$documentClassName];
    }

    /**
     * Returns document mapping with metadata from a document object
     *
     * @param DocumentInterface $document
     *
     * @return array
     */
    public function getMetadataFromObject(DocumentInterface $document)
    {
        return $this->getDocumentReflectionMetadata(new \ReflectionObject($document));
    }

    /**
     * Returns document mapping with metadata
     *
     * @param string $documentClassName
     *
     * @return array
     */
    public function getMetadataFromClass($documentClassName)
    {
        $metadata = $this->getDocumentReflectionMetadata(
            new \ReflectionClass($this->finder->resolveClassName($documentClassName))
        );

        return $metadata;
    }

    /**
     * Gathers annotation data from class.
     *
     * @param \ReflectionClass $reflectionClass Document reflection class to read mapping from.
     *
     * @return array
     */
    private function getDocumentReflectionMetadata(\ReflectionClass $reflectionClass)
    {
        $metadata = $this->parser->parse($reflectionClass);

        return $metadata;
    }
}
