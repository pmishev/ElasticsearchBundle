<?php

namespace Sineflow\ElasticsearchBundle\Mapping;

/**
 * Finds ES documents in bundles.
 */
class DocumentFinder
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
     * @return string
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
     * Returns bundle document paths.
     *
     * TODO: remove this method, as we don't need to get all documents defined in a bundle - they must be explicitly defined for each index manager
     * @param string $bundle
     * @return array
     */
    public function getBundleDocumentPaths($bundle)
    {
        $bundleReflection = new \ReflectionClass($this->getBundleClass($bundle));

        return glob(
            dirname($bundleReflection->getFileName()) .
            DIRECTORY_SEPARATOR . str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $this->getDocumentDir()) .
            DIRECTORY_SEPARATOR . '*.php'
        );
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
