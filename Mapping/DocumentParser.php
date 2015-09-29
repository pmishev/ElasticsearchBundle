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
     * TODO: Move this as a bundle parameter
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
     * @var array Document properties metadata.
     */
    private $propertiesMetadata = [];

    /**
     * @var array Local cache for document properties.
     */
    private $properties = [];

    /**
     * @var LanguageProviderInterface
     */
    private $languageProvider;

    /**
     * @var string
     */
    private $languageSeparator;

    /**
     * @param Reader          $reader            Used for reading annotations.
     * @param DocumentLocator $documentLocator   Used for resolving namespaces.
     * @param string          $languageSeparator String separating the language code from the ML property name
     */
    public function __construct(Reader $reader, DocumentLocator $documentLocator, $languageSeparator)
    {
        $this->reader = $reader;
        $this->documentLocator = $documentLocator;
        $this->languageSeparator = $languageSeparator;
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
                    'propertiesMetadata' => $this->getPropertiesMetadata($reflectionClass),
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
     * Finds properties metadata for every property used in document including parent classes.
     *
     * @param \ReflectionClass $reflectionClass
     *
     * @return array
     */
    private function getPropertiesMetadata(\ReflectionClass $reflectionClass)
    {
        $reflectionName = $reflectionClass->getName();
        if (array_key_exists($reflectionName, $this->propertiesMetadata)) {
            return $this->propertiesMetadata[$reflectionName];
        }

        $propertyMetadata = [];
        /** @var \ReflectionProperty $property */
        foreach ($this->getDocumentPropertiesReflection($reflectionClass) as $name => $property) {
            $propertyAnnotation = $this->getPropertyAnnotationData($property);
            if ($propertyAnnotation !== null) {
                $propertyMetadata[$propertyAnnotation->name] = [
                    'propertyName' => $name,
                    'type' => $propertyAnnotation->type,
                ];

                // If property is multilanguage
                if ($propertyAnnotation instanceof MLProperty) {
                    $propertyMetadata[$propertyAnnotation->name] = array_merge(
                        $propertyMetadata[$propertyAnnotation->name],
                        [
                            'multilanguage' => true,
                        ]
                    );
                }

                // If property is a (nested) object
                if (in_array($propertyAnnotation->type, ['object', 'nested'])) {
                    if (!$propertyAnnotation->objectName) {
                        throw new \InvalidArgumentException(sprintf('Property "%s" in %s is missing "objectName" setting', $name, $reflectionName));
                    }
                    $child = new \ReflectionClass($this->documentLocator->resolveClassName($propertyAnnotation->objectName));
                    $propertyMetadata[$propertyAnnotation->name] = array_merge(
                        $propertyMetadata[$propertyAnnotation->name],
                        [
                            'multiple' => $propertyAnnotation->multiple,
                            'propertiesMetadata' => $this->getPropertiesMetadata($child),
                            'className' => $child->getName(),
                        ]
                    );
                }
            }
        }

        $this->propertiesMetadata[$reflectionName] = $propertyMetadata;

        return $this->propertiesMetadata[$reflectionName];
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

//    /**
//     * @param \ReflectionClass $reflectionClass
//     *
//     * @return array
//     */
//    private function getSkippedProperties(\ReflectionClass $reflectionClass)
//    {
//        /** @var Skip $class */
//        $class = $this->reader->getClassAnnotation($reflectionClass, 'Sineflow\ElasticsearchBundle\Annotation\Skip');
//
//        return $class === null ? [] : $class->value;
//    }
//
//    /**
//     * @param \ReflectionClass $reflectionClass
//     *
//     * @return array
//     */
//    private function getInheritedProperties(\ReflectionClass $reflectionClass)
//    {
//        /** @var Inherit $class */
//        $class = $this->reader->getClassAnnotation($reflectionClass, 'Sineflow\ElasticsearchBundle\Annotation\Inherit');
//
//        return $class === null ? [] : $class->value;
//    }

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
                if ($propertyAnnotation->type != 'string') {
                    throw new \InvalidArgumentException(sprintf('"%s" property in %s is declared as "MLProperty", so can only be of type "string"', $propertyAnnotation->name, $reflectionClass->getName()));
                }
                if (!$this->languageProvider) {
                    throw new \InvalidArgumentException('There must be a service tagged as "sfes.language_provider" in order to use MLProperty');
                }
                foreach ($this->languageProvider->getLanguages() as $language) {
                    $mapping[$propertyAnnotation->name . $this->languageSeparator . $language] = $this->getPropertyMapping($propertyAnnotation, $language, $indexAnalyzers);
                }
                // TODO: This is a temporary hardcode. The application should decide whether it wants to use a default field at all and set its mapping on a global base (or per property?)
                // The custom mapping from the application should be set here, using perhaps some kind of decorator
                $mapping[$propertyAnnotation->name . $this->languageSeparator . self::DEFAULT_LANG_SUFFIX] = [
                    'type' => 'string',
                    'index' => 'not_analyzed'
                ];
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
