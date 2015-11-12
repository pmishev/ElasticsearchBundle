<?php

namespace Sineflow\ElasticsearchBundle\Mapping;

use Doctrine\Common\Cache\CacheProvider;

/**
 * Class for getting type/document metadata.
 */
class DocumentMetadataCollector
{
    /**
     * <index_manager_name> => [
     *      <document_class_short_name> => DocumentMetadata
     *      ...
     * ]
     * ...
     *
     * @var array
     */
    private $metadata = [];

    /**
     * @var array
     */
    private $indexManagers;

    /**
     * @var DocumentLocator
     */
    private $documentLocator;

    /**
     * @var DocumentParser
     */
    private $parser;

    /**
     * @var CacheProvider
     */
    private $cacheProvider;

    /**
     * @param array           $indexManagers   The list of index managers defined
     * @param DocumentLocator $documentLocator For finding documents.
     * @param DocumentParser  $parser          For reading document annotations.
     * @param CacheProvider   $cacheProvider   For caching documents metadata
     */
    public function __construct(array $indexManagers, DocumentLocator $documentLocator, DocumentParser $parser, CacheProvider $cacheProvider = null)
    {
        $this->indexManagers = $indexManagers;
        $this->documentLocator = $documentLocator;
        $this->parser = $parser;
        $this->cacheProvider = $cacheProvider;

        // TODO: enable caching after I do the main logic
//        if ($this->cacheProvider) {
//            $this->metadata = $this->cacheProvider->fetch('sfes.documents_metadata');
//        }

        // If there was no cached metadata, retrieve it now
        if (empty($this->metadata)) {
            $this->fetchDocumentsMetadata();
        }
    }

    /**
     * Retrieves the metadata for all documents in all indices
     */
    private function fetchDocumentsMetadata()
    {

        $documentClasses = [];

        foreach ($this->indexManagers as $indexManagerName => $indexSettings) {
            $indexAnalyzers = isset($indexSettings['settings']['analysis']['analyzer']) ? $indexSettings['settings']['analysis']['analyzer'] : [];

            // Fetches DocumentMetadata objects for the types within the index
            foreach ($indexSettings['types'] as $documentClass) {
                if (isset($documentClasses[$documentClass])) {
                    throw new \InvalidArgumentException(
                        sprintf('You cannot have type %s under "%s" index manager, as it is already managed by "%s" index manager',
                            $documentClass, $indexManagerName, $documentClasses[$documentClass]
                        )
                    );
                }
                $documentClasses[$documentClass] = $indexManagerName;
                $metadata = $this->fetchMetadataFromClass($documentClass, $indexAnalyzers);
                $this->metadata[$indexManagerName][$documentClass] = new DocumentMetadata($metadata);
            }
        }

        // TODO: enable caching after I do the main logic
//        if ($this->cacheProvider) {
//            $this->cacheProvider->save('sfes.documents_metadata', $this->metadata);
//        }

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
     * Return mapping of document classes in short notation (i.e. AppBundle:Product) to ES types
     *
     * @param array $documentClasses Only return those classes if specified
     * @return array
     */
    public function getClassToTypeMap(array $documentClasses = [])
    {
        $result = [];
        foreach ($this->metadata as $index => $documentsMetadata) {
            foreach ($documentsMetadata as $documentClass => $documentMetadata) {
                /** @var DocumentMetadata $documentMetadata */
                $result[$documentClass] = $documentMetadata->getType();
            }
        }

        if ($documentClasses) {
            $result = array_intersect_key($result, array_flip($documentClasses));
        }

        return $result;
    }

    /**
     * Returns all document classes in the collection as keys and the corresponding index manager that manages them as values
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
     * Returns document mapping with metadata
     *
     * @param string $documentClassName
     * @param array  $indexAnalyzers
     *
     * @return array
     */
    public function fetchMetadataFromClass($documentClassName, array $indexAnalyzers)
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
