<?php

namespace Sineflow\ElasticsearchBundle\Annotation;

/**
 * Annotation used to check mapping type during the parsing process.
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class MLProperty extends AbstractProperty
{
    /**
     * {@inheritdoc}
     */
    public function dump(array $options = [])
    {
        $result = parent::dump($options);

        if (!isset($options['language'])) {
            throw new \InvalidArgumentException('No language specified for a multi-language property');
        }

        // Replace {lang} in any property settings
        array_walk($result, function(&$value, $key, $language) {
            $value = str_replace('{lang}', $language, $value);
        }, $options['language']);

        return $result;
    }
}
