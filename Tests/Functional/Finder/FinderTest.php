<?php

namespace Sineflow\ElasticsearchBundle\Tests\Functional\Finder;

use Sineflow\ElasticsearchBundle\Finder\Finder;
use Sineflow\ElasticsearchBundle\Result\DocumentIterator;
use Sineflow\ElasticsearchBundle\Tests\AbstractElasticsearchTestCase;
use Sineflow\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document\Product;

/**
 * Class FinderTest
 */
class FinderTest extends AbstractElasticsearchTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getDataArray()
    {
        return [
            'bar' => [
                'AcmeBarBundle:Product' => [
                    [
                        '_id' => 'doc1',
                        'title' => 'aaa',
                    ],
                    [
                        '_id' => 'doc2',
                        'title' => 'bbb',
                    ],
                    [
                        '_id' => 3,
                        'title' => 'ccc',
                    ],
                ],
            ],
            'foo' => [
                'AcmeFooBundle:Customer' => [
                    [
                        '_id' => 111,
                        'name' => 'Jane Doe',
                        'title' => 'aaa bbb',
                        'active' => true,
                    ],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        // Create and populate indices just once for all tests in this class
        $this->getIndexManager('foo', !$this->hasCreatedIndexManager('foo'));
        $this->getIndexManager('bar', !$this->hasCreatedIndexManager('bar'));
    }

    public function testGet()
    {
        $finder = $this->getContainer()->get('sfes.finder');

        $docAsObject = $finder->get('AcmeBarBundle:Product', 'doc1');
        $this->assertInstanceOf(Product::class, $docAsObject);
        $this->assertEquals('aaa', $docAsObject->title);

        $docAsArray = $finder->get('AcmeBarBundle:Product', 'doc1', Finder::RESULTS_ARRAY);
        $this->assertEquals('aaa', $docAsArray['title']);

        $docAsRaw = $finder->get('AcmeBarBundle:Product', 'doc1', Finder::RESULTS_RAW);
        $this->assertArraySubset([
            '_index' => 'sineflow-esb-test-bar',
            '_type' => 'product',
            '_id' => 'doc1',
            '_version' => 1,
            'found' => true,
            '_source' => ['title' => 'aaa'],
        ], $docAsRaw);

        $docAsObjectKNP = $finder->get('AcmeBarBundle:Product', 'doc1', Finder::RESULTS_OBJECT | Finder::ADAPTER_KNP);
        $this->assertInstanceOf(Product::class, $docAsObjectKNP);
    }

    public function testFindInMultipleTypesAndIndices()
    {
        $finder = $this->getContainer()->get('sfes.finder');

        $searchBody = [
            'query' => [
                'match' => [
                    'title' => 'bbb'
                ]
            ]
        ];

        $res = $finder->find(['AcmeBarBundle:Product', 'AcmeFooBundle:Customer'], $searchBody, Finder::RESULTS_OBJECT, [], $totalHits);
        $this->assertInstanceOf(DocumentIterator::class, $res);
        $this->assertEquals(2, count($res));
    }
}
