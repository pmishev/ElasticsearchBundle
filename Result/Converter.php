<?php

namespace Sineflow\ElasticsearchBundle\Result;

use Sineflow\ElasticsearchBundle\Document\DocumentInterface;
use Sineflow\ElasticsearchBundle\Document\MLProperty;
use Sineflow\ElasticsearchBundle\Mapping\ClassMetadata;
use Sineflow\ElasticsearchBundle\Mapping\DocumentMetadata;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * This class converts array to document object.
 */
class Converter
{
    /**
     * @var DocumentMetadata
     */
    private $documentMetadata;

    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * @var string
     */
    private $languageSeparator;

    /**
     * Constructor.
     *
     * @param DocumentMetadata $documentMetadata
     * @param string           $languageSeparator
     */
    public function __construct(DocumentMetadata $documentMetadata, $languageSeparator)
    {
        $this->documentMetadata = $documentMetadata;
        $this->languageSeparator = $languageSeparator;
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

        /** @var DocumentInterface $object */
        $className = $this->documentMetadata->getClassName();
        $object = $this->assignArrayToObject($data, new $className(), $this->documentMetadata->getPropertiesMetadata());

        $this->setObjectFields($object, $rawData, ['_id', '_score', 'fields _parent', 'fields _ttl']);

        return $object;
    }

    /**
     * Assigns all properties to object. Could be a document or a (nested) object.
     *
     * @param array  $array
     * @param object $object
     * @param array  $propertiesMetadata
     *
     * @return object
     */
    public function assignArrayToObject(array $array, $object, array $propertiesMetadata)
    {
        foreach ($propertiesMetadata as $esField => $propertyMetadata) {
            // Skip fields from the mapping that have no value set, unless they are multilanguage fields
            if (empty($propertyMetadata['multilanguage']) && !isset($array[$esField])) {
                continue;
            }

            if ($propertyMetadata['type'] === 'string' && !empty($propertyMetadata['multilanguage'])) {
                $objectValue = new MLProperty();
                foreach ($array as $fieldName => $value) {
                    $prefixLength = strlen($esField . $this->languageSeparator);
                    if (substr($fieldName, 0, $prefixLength) === $esField . $this->languageSeparator) {
                        $language = substr($fieldName, $prefixLength);
                        $objectValue->setValue($value, $language);
                    }
                }

            } elseif ($propertyMetadata['type'] === 'date') {
                $objectValue = \DateTime::createFromFormat(
                    // TODO: is this 'format' field being set anywhere at all?
                    isset($propertyMetadata['format']) ? $propertyMetadata['format'] : \DateTime::ISO8601,
                    $array[$esField]
                ) ?: $array[$esField];

            } elseif (in_array($propertyMetadata['type'], ['object', 'nested'])) {
                if ($propertyMetadata['multiple']) {
                    $objectValue = new ObjectIterator($this, $array[$esField], $propertyMetadata);
                } else {
                    $objectValue = $this->assignArrayToObject(
                        $array[$esField],
                        new $propertyMetadata['className'](),
                        $propertyMetadata['propertiesMetadata']
                    );
                }

            } else {
                $objectValue = $array[$esField];
            }

            $this->getPropertyAccessor()->setValue($object, $propertyMetadata['propertyName'], $objectValue);
        }

        return $object;
    }

    /**
     * Converts document or (nested) object to an array.
     *
     * @param object $object
     * @param array  $propertiesMetadata
     *
     * @return array
     */
    public function convertToArray($object, $propertiesMetadata = [])
    {
        if (empty($propertiesMetadata)) {
            $propertiesMetadata = $this->documentMetadata->getPropertiesMetadata();
        }

        $array = [];
        // Special fields.
        if ($object instanceof DocumentInterface) {
            $this->setArrayFields($array, $object, ['_id', '_parent', '_ttl']);
        }

        // Variable $name defined in client.
        foreach ($propertiesMetadata as $name => $propertyMetadata) {
            $value = $this->getPropertyAccessor()->getValue($object, $propertyMetadata['propertyName']);

            if (isset($value)) {
                if (array_key_exists('propertiesMetadata', $propertyMetadata)) {
                    $new = [];
                    if ($propertyMetadata['multiple']) {
                        $this->isTraversable($value);
                        foreach ($value as $item) {
                            $this->checkVariableType($item, [$propertyMetadata['className']]);
                            $new[] = $this->convertToArray($item, $propertyMetadata['propertiesMetadata']);
                        }
                    } else {
                        $this->checkVariableType($value, [$propertyMetadata['className']]);
                        $new = $this->convertToArray($value, $propertyMetadata['propertiesMetadata']);
                    }
                    $value = $new;
                }

                if ($value instanceof \DateTime) {
                    $value = $value->format(isset($propertyMetadata['format']) ? $propertyMetadata['format'] : \DateTime::ISO8601);
                }

                if ($value instanceof MLProperty) {
                    foreach ($value->getValues() as $language => $langValue) {
                        $array[$name . $this->languageSeparator . $language] = $langValue;
                    }
                } else {
                    $array[$name] = $value;
                }
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
