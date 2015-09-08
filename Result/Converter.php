<?php

namespace Sineflow\ElasticsearchBundle\Result;

use Sineflow\ElasticsearchBundle\Document\DocumentInterface;
use Sineflow\ElasticsearchBundle\Mapping\ClassMetadata;
//use ONGR\ElasticsearchBundle\Mapping\Proxy\ProxyInterface;
use Sineflow\ElasticsearchBundle\Mapping\DocumentMetadata;
use Sineflow\ElasticsearchBundle\Mapping\DocumentMetadataCollection;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * This class converts array to document object.
 * TODO: remove the commented proxy fields stuff or make it work if it's needed
 */
class Converter
{
//    /**
//     * @var array
//     */
//    private $typesMapping;
//
//    /**
//     * @var array
//     */
//    private $bundlesMapping;

    /**
     * @var DocumentMetadata
     */
    private $documentMetadata;

    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * Constructor.
     *
     * @param DocumentMetadata $documentMetadata
     */
    public function __construct(DocumentMetadata $documentMetadata)
    {
//        $this->typesMapping = $typesMapping;
//        $this->bundlesMapping = $bundlesMapping;
        $this->documentMetadata = $documentMetadata;

    }

    /**
     * Converts raw array to document.
     *
     * @param array $rawData
     *
     * @return DocumentInterface
     *
     * @throws \LogicException
     */
    public function convertToDocument($rawData)
    {
        $data = isset($rawData['_source']) ? $rawData['_source'] : array_map('reset', $rawData['fields']);
//        $proxy = $metadata->getProxyNamespace();

        /** @var DocumentInterface $object */
//        $object = $this->assignArrayToObject($data, new $proxy(), $metadata->getAliases());
        $className = $this->documentMetadata->getClassName();
        $object = $this->assignArrayToObject($data, new $className(), $this->documentMetadata->getAliases());

//        if ($object instanceof ProxyInterface) {
//            $object->__setInitialized(true);
//        }

        $this->setObjectFields($object, $rawData, ['_id', '_score', 'highlight', 'fields _parent', 'fields _ttl']);

        return $object;
    }

    /**
     * Assigns all properties to object.
     *
     * @param array  $array
     * @param object $object
     * @param array  $aliases
     *
     * @return object
     */
    public function assignArrayToObject(array $array, $object, array $aliases)
    {
        foreach ($array as $name => $value) {
            if (!array_key_exists($name, $aliases) || $value === null) {
                $object->{$name} = $value;
                continue;
            }

            if ($aliases[$name]['type'] === 'date') {
                $newValue = \DateTime::createFromFormat(
                    isset($aliases[$name]['format']) ? $aliases[$name]['format'] : \DateTime::ISO8601,
                    $value
                );
                
                $value = $newValue === false ? $value : $newValue;
            }

            if (array_key_exists('aliases', $aliases[$name])) {
                if ($aliases[$name]['multiple']) {
                    $value = new ObjectIterator($this, $value, $aliases[$name]);
                } else {
                    $value = $this->assignArrayToObject(
                        $value,
                        //new $aliases[$name]['proxyNamespace'](),
                        new $aliases[$name]['className'](),
                        $aliases[$name]['aliases']
                    );
                }
            }

            $this->getPropertyAccessor()->setValue($object, $aliases[$name]['propertyName'], $value);
        }

        return $object;
    }

    /**
     * Converts object to an array.
     *
     * @param DocumentInterface $object
     * @param array             $aliases
     *
     * @return array
     */
    public function convertToArray($object, $aliases = [])
    {
        if (empty($aliases)) {
            $aliases = $this->getAlias($object);
            // TODO: Should I have metadata for nested objects in the metadatacollection? Should I pass the entire collection to the converter?
//            $this->documentMetadata->getAliases();
        }

        $array = [];
        // Special fields.
        if ($object instanceof DocumentInterface) {
            $this->setArrayFields($array, $object, ['_id', '_parent', '_ttl']);
        }

        // Variable $name defined in client.
        foreach ($aliases as $name => $alias) {
            $value = $this->getPropertyAccessor()->getValue($object, $alias['propertyName']);

            if (isset($value)) {
                if (array_key_exists('aliases', $alias)) {
                    $new = [];
                    if ($alias['multiple']) {
                        $this->isTraversable($value);
                        foreach ($value as $item) {
//                            $this->checkVariableType($item, [$alias['namespace'], $alias['proxyNamespace']]);
                            $this->checkVariableType($item, [$alias['className']]);
                            $new[] = $this->convertToArray($item, $alias['aliases']);
                        }
                    } else {
//                        $this->checkVariableType($value, [$alias['namespace'], $alias['proxyNamespace']]);
                        $this->checkVariableType($value, [$alias['className']]);
                        $new = $this->convertToArray($value, $alias['aliases']);
                    }
                    $value = $new;
                }

                if ($value instanceof \DateTime) {
                    $value = $value->format(isset($alias['format']) ? $alias['format'] : \DateTime::ISO8601);
                }

                $array[$name] = $value;
            }
        }

        return $array;
    }

    /**
     * Sets fields into object from raw response.
     *
     * @param object $object      Object to set values to.
     * @param array  $rawResponse Array to take values from.
     * @param array  $fields      Values to take.
     */
    private function setObjectFields($object, $rawResponse, $fields = [])
    {
        foreach ($fields as $field) {
            $path = $this->getPropertyPathToAccess($field);
            $value = $this->getPropertyAccessor()->getValue($rawResponse, $path);

            if ($value !== null) {
                if (strpos($path, 'highlight') !== false) {
                    $value = new DocumentHighlight($value);
                }

                $this->getPropertyAccessor()->setValue($object, $this->getPropertyToAccess($field), $value);
            }
        }
    }

    /**
     * Sets fields into array from object.
     *
     * @param array  $array  To set values to.
     * @param object $object Take values from.
     * @param array  $fields Fields to set.
     */
    private function setArrayFields(&$array, $object, $fields = [])
    {
        foreach ($fields as $field) {
            $value = $this->getPropertyAccessor()->getValue($object, $this->getPropertyToAccess($field));

            if ($value !== null) {
                $this
                    ->getPropertyAccessor()
                    ->setValue($array, $this->getPropertyPathToAccess($field), $value);
            }
        }
    }

    /**
     * Returns property to access for object used by property accessor.
     *
     * @param string $field
     *
     * @return string
     */
    private function getPropertyToAccess($field)
    {
        $deep = strpos($field, ' ');
        if ($deep !== false) {
            $field = substr($field, $deep + 1);
        }

        return $field;
    }

    /**
     * Returns property to access for array used by property accessor.
     *
     * @param string $field
     *
     * @return string
     */
    private function getPropertyPathToAccess($field)
    {
        return '[' . str_replace(' ', '][', $field) . ']';
    }

    /**
     * Check if class matches the expected one.
     *
     * @param object $object
     * @param array  $expectedClasses
     *
     * @throws \InvalidArgumentException
     */
    private function checkVariableType($object, array $expectedClasses)
    {
        if (!is_object($object)) {
            $msg = 'Expected variable of type object, got ' . gettype($object) . ". (field isn't multiple)";
            throw new \InvalidArgumentException($msg);
        }

        $class = get_class($object);
        if (!in_array($class, $expectedClasses)) {
            throw new \InvalidArgumentException("Expected object of type {$expectedClasses[0]}, got {$class}.");
        }
    }

    /**
     * Check if object is traversable, throw exception otherwise.
     *
     * @param mixed $value
     *
     * @return bool
     *
     * @throws \InvalidArgumentException
     */
    private function isTraversable($value)
    {
        if (!(is_array($value) || (is_object($value) && $value instanceof \Traversable))) {
            throw new \InvalidArgumentException("Variable isn't traversable, although field is set to multiple.");
        }

        return true;
    }

//    /**
//     * Returns aliases for certain document.
//     *
//     * @param DocumentInterface $document
//     *
//     * @return array
//     */
//    private function getAlias($document)
//    {
//        $class = get_class($document);
//
//        return $this->documentMetadataCollection->getDocumentMetadata($class)->getAliases();
//    }

    /**
     * Returns property accessor instance.
     *
     * @return PropertyAccessor
     */
    private function getPropertyAccessor()
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
                ->enableMagicCall()
                ->getPropertyAccessor();
        }

        return $this->propertyAccessor;
    }
}
