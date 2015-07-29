<?php

namespace Sineflow\ElasticsearchBundle\Mapping;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Holds document metadata.
 */
class DocumentMetadata
{
    /**
     * @var array
     */
    private $metadata;

    /**
     * @var string
     */
    private $type;

    /**
     * Resolves metadata.
     *
     * @param array $metadata
     */
    public function __construct(array $metadata)
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->type = key($metadata);
        $this->metadata = $resolver->resolve($metadata[$this->type]);
    }

    /**
     * Configures options resolver.
     *
     * @param OptionsResolver $optionsResolver
     */
    protected function configureOptions(OptionsResolver $optionsResolver)
    {
//        $optionsResolver->setRequired(['properties', 'fields', 'aliases', 'namespace', 'proxyNamespace', 'class', 'objects']);
        $optionsResolver->setRequired(['properties', 'fields', 'objects']);
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->metadata['properties'];
    }

//    /**
//     * @return array
//     */
//    public function getAliases()
//    {
//        return $this->metadata['aliases'];
//    }

    /**
     * @return array
     */
    public function getObjects()
    {
        return $this->metadata['objects'];
    }

//    /**
//     * @return string
//     */
//    public function getNamespace()
//    {
//        return $this->metadata['namespace'];
//    }
//
//    /**
//     * @return string
//     */
//    public function getProxyNamespace()
//    {
//        return $this->metadata['proxyNamespace'];
//    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->metadata['fields'];
    }

//    /**
//     * @return string
//     */
//    public function getClassName()
//    {
//        return $this->metadata['class'];
//    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }
}
