<?php

namespace Sineflow\ElasticsearchBundle\LanguageProvider;

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
     * @deprecated Default language can't be set globally, as each entity may have its own default language
     */
    public function getDefaultLanguage();
}
