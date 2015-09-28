<?php

namespace Sineflow\ElasticsearchBundle\Mapping;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\Reader;
use Sineflow\ElasticsearchBundle\Annotation\AbstractProperty;
use Sineflow\ElasticsearchBundle\Annotation\Document;
use Sineflow\ElasticsearchBundle\Annotation\MLProperty;
use Sineflow\ElasticsearchBundle\Annotation\MultiField;
use Sineflow\ElasticsearchBundle\LanguageProvider\LanguageProviderInterface;
//use Sineflow\ElasticsearchBundle\Annotation\Inherit;
//use Sineflow\ElasticsearchBundle\Annotation\Skip;

/**
 * Document parser used for reading document annotations.
 */
class DocumentParser
{
    /**
     * @const string
     */
    const PROPERTY_ANNOTATION = 'Sineflow\ElasticsearchBundle\Annotation\AbstractProperty';

    /**
     * @const string
     */
    const DOCUMENT_ANNOTATION = 'Sineflow\ElasticsearchBundle\Annotation\Document';

    /**
     * @const string
     */
    const LANGUAGE_SEPARATOR = '-';

    /**
     * @const string
     */
    const DEFAULT_LANG_SUFFIX = 'default';

    /**
     * @var Reader Used to read document annotations.
     */
    private $reader;

    /**
     * @var DocumentLocator Used to find documents.
     */
    private $documentLocator;

    /**
     * @var array Contains gathered objects which later adds to documents.
     */
    private $objects = [];

    /**
     * @var array Document properties aliases.
     * TODO: can we do without these?
     */
    private $aliases = [];

    /**
     * @var array Local cache for document properties.
     */
    private $properties = [];

    /**
     * @var LanguageProviderInterface
     */
    private $languageProvider;

    /**
     * @param Reader          $reader          Used for reading annotations.
     * @param DocumentLocator $documentLocator Used for resolving namespaces.
     */
    public function __construct(Reader $reader, DocumentLocator $documentLocator)
    {
        $this->reader = $reader;
        $this->documentLocator = $documentLocator;
        $this->registerAnnotations();
    }

    /**
     * @param LanguageProviderInterface $languageProvider
     */
    public function setLanguageProvider(LanguageProviderInterface $languageProvider)
    {
        $this->languageProvider = $languageProvider;
    }

    /**
     * Parses documents by used annotations and returns mapping for elasticsearch with some extra metadata.
     *
     * @param \ReflectionClass $reflectionClass
     * @param array            $indexAnalyzers
     *
     * @return array
     */
    public function parse(\ReflectionClass $reflectionClass, array $indexAnalyzers)
    {
        /** @var Document $class */
        $class = $this
            ->reader
            ->getClassAnnotation($reflectionClass, self::DOCUMENT_ANNOTATION);

        if ($class !== null && $class->create) {
            if ($class->parent !== null) {
                $parent = $this->getDocumentParentType(
                    new \ReflectionClass($this->documentLocator->resolveClassName($class->parent))
                );
            } else {
                $parent = null;
            }
            $type = $this->getDocumentType($reflectionClass, $class);

            $properties = $this->getProperties($reflectionClass, $indexAnalyzers);

            return [
                $type => [
                    'properties' => $properties,
                    'fields' => array_merge(
                        $class->dump(),
                        ['_parent' => $parent === null ? null : ['type' => $parent]]
                    ),
                    'aliases' => $this->getAliases($reflectionClass),
                    'objects' => $this->getObjects(),
                    'repositoryClass' => $class->repositoryClass,
                    //'namespace' => $reflectionClass->getName(), // renamed to className below
                    'className' => $reflectionClass->getName(),
                    // TODO: what do I need this for?
                    // 'class' => $reflectionClass->getShortName(),
                ],
            ];
        }

        return [];
    }

    /**
     * Returns property annotation data from reader.
     *
     * @param \ReflectionProperty $property
     *
     * @return AbstractProperty
     */
    public function getPropertyAnnotationData($property)
    {
        return $this->reader->getPropertyAnnotation($property, self::PROPERTY_ANNOTATION);
    }

    /**
     * Returns objects used in document.
     *
     * @return array
     */
    private function getObjects()
    {
        return array_keys($this->objects);
    }

    /**
     * Finds aliases for every property used in document including parent classes.
     *
     * @param \ReflectionClass $reflectionClass
     *
     * @return array
     */
    private function getAliases(\ReflectionClass $reflectionClass)
    {
        $reflectionName = $reflectionClass->getName();
        if (array_key_exists($reflectionName, $this->aliases)) {
            return $this->aliases[$reflectionName];
        }

        $alias = [];
        /** @var \ReflectionProperty $property */
        foreach ($this->getDocumentPropertiesReflection($reflectionClass) as $name => $property) {
            $propertyAnnotation = $this->getPropertyAnnotationData($property);
            if ($propertyAnnotation !== null) {
                $alias[$propertyAnnotation->name] = [
                    'propertyName' => $name,
                    'type' => $propertyAnnotation->type,
                ];
                // If property is a (nested) object
                if ($propertyAnnotation->objectName) {
                    $child = new \ReflectionClass($this->documentLocator->resolveClassName($propertyAnnotation->objectName));
                    $alias[$propertyAnnotation->name] = array_merge(
                        $alias[$propertyAnnotation->name],
                        [
                            'multiple' => $propertyAnnotation->multiple,
                            'aliases' => $this->getAliases($child),
                            'className' => $child->getName(),
                        ]
                    );
                }
            }
        }

        $this->aliases[$reflectionName] = $alias;

        return $this->aliases[$reflectionName];
    }

    /**
     * Registers annotations to registry so that it could be used by reader.
     */
    private function registerAnnotations()
    {
        $annotations = [
            'Document',
            'Property',
            'Object',
            'Nested',
            'MultiField',
//            'Inherit',
//            'Skip',
        ];

        foreach ($annotations as $annotation) {
            AnnotationRegistry::registerFile(__DIR__ . "/../Annotation/{$annotation}.php");
        }
    }

    /**
     * Returns document parent.
     *
     * @param \ReflectionClass $reflectionClass
     *
     * @return string|null
     */
    private function getDocumentParentType(\ReflectionClass $reflectionClass)
    {
        /** @var Document $class */
        $class = $this->reader->getClassAnnotation($reflectionClass, 'Sineflow\ElasticsearchBundle\Annotation\Document');

        return $class ? $this->getDocumentType($reflectionClass, $class) : null;
    }

    /**
     * @param \ReflectionClass $reflectionClass
     *
     * @return array
     */
    private function getSkippedProperties(\ReflectionClass $reflectionClass)
    {
        /** @var Skip $class */
        $class = $this->reader->getClassAnnotation($reflectionClass, 'Sineflow\ElasticsearchBundle\Annotation\Skip');

        return $class === null ? [] : $class->value;
    }

    /**
     * @param \ReflectionClass $reflectionClass
     *
     * @return array
     */
    private function getInheritedProperties(\ReflectionClass $reflectionClass)
    {
        /** @var Inherit $class */
        $class = $this->reader->getClassAnnotation($reflectionClass, 'Sineflow\ElasticsearchBundle\Annotation\Inherit');

        return $class === null ? [] : $class->value;
    }

    /**
     * Returns document type.
     *
     * @param \ReflectionClass $reflectionClass
     * @param Document         $document
     *
     * @return string
     */
    private function getDocumentType(\ReflectionClass $reflectionClass, Document $document)
    {
        return empty($document->type) ? $reflectionClass->getShortName() : $document->type;
    }

    /**
     * Returns all defined properties including private from parents.
     *
     * @param \ReflectionClass $reflectionClass
     *
     * @return array
     */
    private function getDocumentPropertiesReflection(\ReflectionClass $reflectionClass)
    {
        if (in_array($reflectionClass->getName(), $this->properties)) {
            return $this->properties[$reflectionClass->getName()];
        }

        $properties = [];

        foreach ($reflectionClass->getProperties() as $property) {
            if (!in_array($property->getName(), $properties)) {
                $properties[$property->getName()] = $property;
            }
        }

        $parentReflection = $reflectionClass->getParentClass();
        if ($parentReflection !== false) {
            $properties = array_merge(
                $properties,
                array_diff_key($this->getDocumentPropertiesReflection($parentReflection), $properties)
            );
        }

        $this->properties[$reflectionClass->getName()] = $properties;

        return $properties;
    }

    /**
     * Returns properties of reflection class.
     *
     * @param \ReflectionClass $reflectionClass Class to read properties from.
     * @param array            $indexAnalyzers
     *
     * @return array
     */
    private function getProperties(\ReflectionClass $reflectionClass, array $indexAnalyzers = [])
    {
        $mapping = [];
        /** @var \ReflectionProperty $property */
        foreach ($this->getDocumentPropertiesReflection($reflectionClass) as $name => $property) {
            $propertyAnnotation = $this->getPropertyAnnotationData($property);

            if (empty($propertyAnnotation)) {
                continue;
            }

            // If it is a multi-language property
            if ($propertyAnnotation instanceof MLProperty) {
                if (!$this->languageProvider) {
                    throw new \InvalidArgumentException('There must be a service tagged as "sfes.language_provider" in order to use MLProperty');
                }
                foreach ($this->languageProvider->getLanguages() as $language) {
                    $mapping[$propertyAnnotation->name . self::LANGUAGE_SEPARATOR . $language] = $this->getPropertyMapping($propertyAnnotation, $language, $indexAnalyzers);
                }
                $mapping[$propertyAnnotation->name . self::LANGUAGE_SEPARATOR . self::DEFAULT_LANG_SUFFIX] = $this->getPropertyMapping($propertyAnnotation, $this->languageProvider->getDefaultLanguage(), $indexAnalyzers);
            } else {
                $mapping[$propertyAnnotation->name] = $this->getPropertyMapping($propertyAnnotation);
            }

        }

        return $mapping;
    }

    private function getPropertyMapping(AbstractProperty $propertyAnnotation, $language = null, array $indexAnalyzers = [])
    {
        $propertyMapping = $propertyAnnotation->dump([
            'language' => $language,
            'indexAnalyzers' => $indexAnalyzers
        ]);

        // Object.
        if (in_array($propertyAnnotation->type, ['object', 'nested']) && !empty($propertyAnnotation->objectName)) {
            $propertyMapping = array_replace_recursive($propertyMapping, $this->getObjectMapping($propertyAnnotation->objectName));
        }

        // MultiField.
        if (isset($propertyMapping['fields']) && !in_array($propertyAnnotation->type, ['object', 'nested'])) {
            $fieldsMap = [];
            /** @var MultiField $field */
            foreach ($propertyMapping['fields'] as $field) {
                $fieldsMap[$field->name] = $field->dump();
            }
            $propertyMapping['fields'] = $fieldsMap;
        }

        // Raw override.
        if (isset($propertyMapping['raw'])) {
            $raw = $propertyMapping['raw'];
            unset($propertyMapping['raw']);
            $propertyMapping = array_merge($propertyMapping, $raw);
        }

        return $propertyMapping;
    }

    /**
     * Returns object mapping.
     *
     * Loads from cache if it's already loaded.
     *
     * @param string $objectName
     *
     * @return array
     */
    private function getObjectMapping($objectName)
    {
        $namespace = $this->documentLocator->resolveClassName($objectName);

        if (array_key_exists($namespace, $this->objects)) {
            return $this->objects[$namespace];
        }

        $this->objects[$namespace] = $this->getRelationMapping(new \ReflectionClass($namespace));

        return $this->objects[$namespace];
    }

    /**
     * Returns relation mapping by its reflection.
     *
     * @param \ReflectionClass $reflectionClass
     *
     * @return array|null
     */
    private function getRelationMapping(\ReflectionClass $reflectionClass)
    {
        if ($this->reader->getClassAnnotation($reflectionClass, 'Sineflow\ElasticsearchBundle\Annotation\Object')
            || $this->reader->getClassAnnotation($reflectionClass, 'Sineflow\ElasticsearchBundle\Annotation\Nested')
        ) {
            return ['properties' => $this->getProperties($reflectionClass)];
        }

        return null;
    }
}
