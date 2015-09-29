<?php

namespace Sineflow\ElasticsearchBundle\Mapping;

/**
 * Finds ES document classes in bundles.
 */
class DocumentLocator
{
    /**
     * @var array All bundles available in the application
     */
    private $bundles;

    /**
     * @var string Directory in bundle to load documents from.
     */
    private $documentDir = 'Document';

    /**
     * @param array $bundles Parameter kernel.bundles from service container.
     */
    public function __construct(array $bundles)
    {
        $this->bundles = $bundles;
    }

    /**
     * Document directory in bundle to load documents from.
     *
     * @param string $documentDir
     */
    public function setDocumentDir($documentDir)
    {
        $this->documentDir = $documentDir;
    }

    /**
     * Returns directory name in which documents should be put.
     *
     * @return string
     */
    public function getDocumentDir()
    {
        return $this->documentDir;
    }

    /**
     * Resolves document class name from short syntax.
     *
     * @param string $className Short syntax for class name (e.g AppBundle:Product)
     * @return string Fully qualified class name
     */
    public function resolveClassName($className)
    {
        if (strpos($className, ':') !== false) {
            list($bundleName, $document) = explode(':', $className);
            $bundleClass = $this->getBundleClass($bundleName);
            $className = substr($bundleClass, 0, strrpos($bundleClass, '\\')) . '\\' .
                str_replace('/', '\\', $this->getDocumentDir()) . '\\' . $document;
        }

        return $className;
    }

    /**
     * Return the short name for a fully qualified document class name
     *
     * @param string $className Fully qualified class name
     * @return string Class name in short notation (e.g. AppBundle:Product)
     */
    public function getShortClassName($className)
    {
        if (strpos($className, ':') === false) {
            $className = str_replace('\\\\', ':', str_replace(str_replace('/', '\\', $this->getDocumentDir()), '', $className));
        }

        return $className;
    }

    /**
     * Returns bundle class name
     *
     * @param string $bundleName
     * @return string
     * @throws \LogicException
     */
    private function getBundleClass($bundleName)
    {
        if (array_key_exists($bundleName, $this->bundles)) {
            return $this->bundles[$bundleName];
        }

        throw new \LogicException(sprintf('Bundle \'%s\' does not exist.', $bundleName));
    }
}
