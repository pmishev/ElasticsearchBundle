<?php

namespace Sineflow\ElasticsearchBundle\Document\LanguageProvider;

/**
 * Defines the interface that language providers must implement
 * A language provider supplies all available languages in the application
 */
interface LanguageProviderInterface
{
    /**
     * Returns array of available language codes
     *
     * @return array
     */
    public function getLanguages();

    /**
     * Returns the default language code, which must be one of the codes returned by getLanguages()
     *
     * @return string
     */
    public function getDefaultLanguage();
}
