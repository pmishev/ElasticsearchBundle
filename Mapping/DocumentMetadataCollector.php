<?php

namespace Sineflow\ElasticsearchBundle\Mapping;

/**
 * DocumentParser wrapper for getting type/document mapping.
 */
class DocumentMetadataCollector
{
    /**
     * @var DocumentLocator
     */
    private $documentLocator;

    /**
     * @var DocumentParser
     */
    private $parser;

    /**
     * @param DocumentLocator $documentLocator For finding documents.
     * @param DocumentParser  $parser          For reading document annotations.
     */
    public function __construct(DocumentLocator $documentLocator, DocumentParser $parser)
    {
        $this->documentLocator = $documentLocator;
        $this->parser = $parser;
    }

    /**
     * Returns document mapping with metadata
     *
     * @param string $documentClassName
     * @param array  $indexAnalyzers
     *
     * @return array
     */
    public function getMetadataFromClass($documentClassName, array $indexAnalyzers)
    {
        $metadata = $this->getDocumentReflectionMetadata(
            new \ReflectionClass($this->documentLocator->resolveClassName($documentClassName)),
            $indexAnalyzers
        );

        return $metadata;
    }

    /**
     * Gathers annotation data from class.
     *
     * @param \ReflectionClass $reflectionClass Document reflection class to read mapping from.
     * @param array            $indexAnalyzers
     *
     * @return array
     */
    private function getDocumentReflectionMetadata(\ReflectionClass $reflectionClass, array $indexAnalyzers)
    {
        $metadata = $this->parser->parse($reflectionClass, $indexAnalyzers);

        return $metadata;
    }
}
