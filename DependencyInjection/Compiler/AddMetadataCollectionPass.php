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
        $indices = $container->getParameter('sfes.indices');

        foreach ($indices as $indexManagerName => $indexSettings) {
            $documentsMetadataDefinitions[$indexManagerName] = $this->getDocumentsMetadataDefinitions($container, $indexSettings);
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

    /**
     * Fetches metadata service definitions for the types within an index
     *
     * @param ContainerBuilder $container
     * @param array            $indexSettings
     *
     * @return array
     */
    private function getDocumentsMetadataDefinitions(ContainerBuilder $container, $indexSettings)
    {
        $result = [];
        $indexAnalyzers = isset($indexSettings['settings']['analysis']['analyzer']) ? $indexSettings['settings']['analysis']['analyzer'] : [];

        /** @var DocumentMetadataCollector $metaCollector */
        $metaCollector = $container->get('sfes.document_metadata_collector');
        foreach ($indexSettings['types'] as $typeClass) {
            foreach ($metaCollector->getMetadataFromClass($typeClass, $indexAnalyzers) as $typeName => $metadata) {
                $metadataDefinition = new Definition('Sineflow\ElasticsearchBundle\Mapping\DocumentMetadata');
                $metadataDefinition->addArgument([$typeName => $metadata]);
                $result[$typeClass] = $metadataDefinition;
            }
        }

        return $result;
    }
}
