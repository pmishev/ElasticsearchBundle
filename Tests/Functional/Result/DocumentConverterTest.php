<?php

namespace Sineflow\ElasticsearchBundle\Tests\Functional\Result;

use Sineflow\ElasticsearchBundle\Document\MLProperty;
use Sineflow\ElasticsearchBundle\Tests\AbstractContainerAwareTestCase;
use Sineflow\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document\ObjCategory;
use Sineflow\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document\Product;

class DocumentConverterTest extends AbstractContainerAwareTestCase
{
    public function testAssignArrayToObject()
    {
        $converter = $this->getContainer()->get('sfes.document_converter');
        $metadataCollector = $this->getContainer()->get('sfes.document_metadata_collector');

        // Case 1: Test all fields correctly set
        $rawDoc = [
            '_id' => 'doc1',
            'title' => 'Foo Product',
            'category' => [
                'title' => 'Bar',
            ],
            'related_categories' => [
                [
                    'title' => 'Acme',
                ],
            ],
            'ml_info' => 'should be skipped',
            'ml_info-en' => 'info in English',
            'ml_info-fr' => 'info in French',
        ];

        $product = new Product();
        $result = $converter->assignArrayToObject(
            $rawDoc,
            $product,
            $metadataCollector->getDocumentMetadata('AcmeBarBundle:Product')->getPropertiesMetadata()
        );

        $this->assertSame($product, $result);

        $this->assertEquals('Foo Product', $product->title);
        $this->assertEquals('doc1', $product->id);
        $this->assertInstanceOf(ObjCategory::class, $product->category);
        $this->assertContainsOnlyInstancesOf(ObjCategory::class, $product->relatedCategories);
        $this->assertInstanceOf(MLProperty::class, $product->mlInfo);
        $this->assertEquals('info in English', $product->mlInfo->getValue('en'));
        $this->assertEquals('info in French', $product->mlInfo->getValue('fr'));

        // Case 2: Test empty data
        $rawDoc = [
        ];

        $product = new Product();
        $converter->assignArrayToObject(
            $rawDoc,
            $product,
            $metadataCollector->getDocumentMetadata('AcmeBarBundle:Product')->getPropertiesMetadata()
        );
        $this->assertNull($product->title);
        $this->assertNull($product->category);
        $this->assertNull($product->relatedCategories);
        $this->assertNull($product->mlInfo);

    }

    public function testConvertToArray()
    {
        // TODO:
    }

}
