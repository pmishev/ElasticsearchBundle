<?php

namespace Sineflow\ElasticsearchBundle\DependencyInjection\Compiler;

use Sineflow\ElasticsearchBundle\Mapping\DocumentMetadataCollector;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiles elastic search data.
 */
class AddMetadataCollectionPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $documentsMetadataDefinitions = [];
        $documentClasses = [];
        /** @var DocumentMetadataCollector $metaCollector */
        $metaCollector = $container->get('sfes.document_metadata_collector');
        $indices = $container->getParameter('sfes.indices');

        foreach ($indices as $indexManagerName => $indexSettings) {
            $indexAnalyzers = isset($indexSettings['settings']['analysis']['analyzer']) ? $indexSettings['settings']['analysis']['analyzer'] : [];

            // Fetches DocumentMetadata service definitions for the types within the index
            foreach ($indexSettings['types'] as $documentClass) {
                if (isset($documentClasses[$documentClass])) {
                    throw new \InvalidArgumentException(
                        sprintf('You cannot have type %s under "%s" index manager, as it is already managed by "%s" index manager',
                            $documentClass, $indexManagerName, $documentClasses[$documentClass]
                        ));
                }
                $documentClasses[$documentClass] = $indexManagerName;
                $metadata = $metaCollector->fetchMetadataFromClass($documentClass, $indexAnalyzers);
                $metadataDefinition = new Definition('Sineflow\ElasticsearchBundle\Mapping\DocumentMetadata');
                $metadataDefinition->addArgument($metadata);
                $documentsMetadataDefinitions[$indexManagerName][$documentClass] = $metadataDefinition;
            }
        }

        $documentsMetadataCollection = new Definition(
            'Sineflow\ElasticsearchBundle\Mapping\DocumentMetadataCollection',
            [
                new Reference('sfes.document_locator'),
                $documentsMetadataDefinitions,
            ]
        );
        $container->setDefinition('sfes.document_metadata_collection', $documentsMetadataCollection);
    }
}
