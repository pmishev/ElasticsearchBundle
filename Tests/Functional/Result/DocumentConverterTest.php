<?php

namespace Sineflow\ElasticsearchBundle\Tests\Functional\Result;

use Sineflow\ElasticsearchBundle\Tests\AbstractContainerAwareTestCase;
use Sineflow\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document\ObjCategory;
use Sineflow\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document\Product;

class DocumentConverterTest extends AbstractContainerAwareTestCase
{
    public function testAssignArrayToObject()
    {
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
        ];

        $converter = $this->getContainer()->get('sfes.document_converter');
        $metadataCollector = $this->getContainer()->get('sfes.document_metadata_collector');

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
        // TODO: check the other properties as well as the ML one
    }

    public function testConvertToArray()
    {
        // TODO:
    }

}
