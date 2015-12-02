<?php

namespace Sineflow\ElasticsearchBundle\Tests\Unit\Mapping;

use Sineflow\ElasticsearchBundle\Mapping\DocumentLocator;

class DocumentLocatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Data provider for testDocumentDir tests.
     *
     * @return array
     */
    public function getTestData()
    {
        $out = [];

        // Case #0.
        $out[] = [
            'Sineflow\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document\Product',
            'AcmeBarBundle:Product',
            true,
        ];

        // Case #1.
        $out[] = [
            'Sineflow\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document\Product',
            'AcmeBarBundle:Product',
        ];

        return $out;
    }

    /**
     * Tests if correct namespace is returned.
     *
     * @param string $expectedNamespace
     * @param string $document
     * @param bool   $testPath
     *
     * @dataProvider getTestData
     */
    public function testDocumentDir($expectedNamespace, $document, $testPath = false)
    {
        $locator = new DocumentLocator($this->getBundles());

        $this->assertEquals($expectedNamespace, $locator->resolveClassName($document));
        if ($testPath) {
            $this->assertGreaterThan(0, count($locator->getAllDocumentDirs()));
        }
    }

    /**
     * @return array
     */
    public function getBundles()
    {
        return ['AcmeBarBundle' => 'Sineflow\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\AcmeBarBundle'];
    }
}
