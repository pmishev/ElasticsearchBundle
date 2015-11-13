<?php

namespace Sineflow\ElasticsearchBundle\Manager;

use Sineflow\ElasticsearchBundle\Document\Provider\ProviderRegistry;
use Sineflow\ElasticsearchBundle\Finder\Finder;
use Sineflow\ElasticsearchBundle\Mapping\DocumentMetadataCollector;

/**
 * Factory for index manager services
 */
class IndexManagerFactory
{
    /**
     * @var DocumentMetadataCollector
     */
    private $metadataCollector;

    /**
     * @var ProviderRegistry
     */
    private $providerRegistry;

    /**
     * @var Finder
     */
    private $finder;

    /**
     * @var string The separator string between property names and language codes for ML properties
     */
    private $languageSeparator;

    /**
     * @var IndexManager[] Array keeping references to the created index managers
     */
    private $indexManagers;

    /**
     * @param DocumentMetadataCollector $metadataCollector
     * @param ProviderRegistry          $providerRegistry
     * @param Finder                    $finder
     * @param string                    $languageSeparator
     */
    public function __construct(
        DocumentMetadataCollector $metadataCollector,
        ProviderRegistry $providerRegistry,
        Finder $finder,
        $languageSeparator)
    {
        $this->metadataCollector = $metadataCollector;
        $this->providerRegistry = $providerRegistry;
        $this->finder = $finder;
        $this->languageSeparator = $languageSeparator;
    }

    /**
     * @param string            $managerName
     * @param ConnectionManager $connection
     * @param array             $indexSettings
     * @return IndexManager
     */
    public function createManager(
        $managerName,
        ConnectionManager $connection,
        array $indexSettings)
    {
        $manager = new IndexManager(
            $managerName,
            $connection,
            $this->metadataCollector,
            $this->providerRegistry,
            $this->finder,
            $this->getIndexParams($managerName, $indexSettings),
            $this->languageSeparator
        );

        $manager->setUseAliases($indexSettings['use_aliases']);

        $this->indexManagers[$managerName] = $manager;

        return $manager;
    }

    /**
     * Returns params for index.
     *
     * @param string           $indexManagerName
     * @param array            $indexSettings
     * @return array
     */
    private function getIndexParams($indexManagerName, array $indexSettings)
    {
        $index = ['index' => $indexSettings['name']];

        if (!empty($indexSettings['settings'])) {
            $index['body']['settings'] = $indexSettings['settings'];
        }

        $mappings = [];

        $metadata = $this->metadataCollector->getDocumentsMetadataForIndex($indexManagerName);
        foreach ($metadata as $className => $documentMetadata) {
            $mappings[$documentMetadata->getType()] = $documentMetadata->getClientMapping();
        }

        if (!empty($mappings)) {
            $index['body']['mappings'] = $mappings;
        }

        return $index;
    }

}