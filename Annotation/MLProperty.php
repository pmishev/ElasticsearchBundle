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
    const LANGUAGE_PLACEHOLDER = '{lang}';

    //TODO: Move this as a bundle parameter
    const DEFAULT_LANG_SUFFIX = 'default';

    /**
     * {@inheritdoc}
     */
    public function dump(array $options = [])
    {
        $result = parent::dump($options);

        if (!isset($options['language'])) {
            throw new \InvalidArgumentException('Language not specified');
        }
        if (!isset($options['indexAnalyzers'])) {
            throw new \InvalidArgumentException('Available index analyzers missing');
        }

        // Replace {lang} in any analyzers with the respective language
        // If no analyzer is defined for a certain language, replace {lang} with 'default'
        array_walk($result, function(&$value, $key, $options) {
            if (in_array($key, ['analyzer', 'index_analyzer', 'search_analyzer']) && false !== strpos($value, self::LANGUAGE_PLACEHOLDER)) {
                // Get the names of all available analyzers in the index
                $indexAnalyzers = array_keys($options['indexAnalyzers']);

                // Make sure a default analyzer is defined, even if we don't need it right now
                // because, if a new language is added and we don't have an analyzer for it, ES mapping would fail
                $defaultAnalyzer = str_replace(self::LANGUAGE_PLACEHOLDER, self::DEFAULT_LANG_SUFFIX, $value);
                if (!in_array($defaultAnalyzer, $indexAnalyzers)) {
                    throw new \LogicException(sprintf('There must be a default language analyzer "%s" defined for index', $defaultAnalyzer));
                }

                $value = str_replace(self::LANGUAGE_PLACEHOLDER, $options['language'], $value);
                if (!in_array($value, $indexAnalyzers)) {
                    $value = $defaultAnalyzer;
                }
            }
        }, $options);

        return $result;
    }
}
