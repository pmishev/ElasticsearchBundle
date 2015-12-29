<?php
/*
namespace Sineflow\ElasticsearchBundle\Tests\Unit\Result;

use Sineflow\ElasticsearchBundle\Mapping\DocumentMetadata;
use Sineflow\ElasticsearchBundle\Result\DocumentConverter;

class DocumentConverterTest extends \PHPUnit_Framework_TestCase
{
    public function testConvertToDocument()
    {
        $documentMetadataCollectorMock = $this->getMockBuilder('\Sineflow\ElasticsearchBundle\Mapping\DocumentMetadataCollector')
            ->disableOriginalConstructor()
            ->getMock();

        $metadata = new DocumentMetadata(
            array (
                'type' => 'media',
                'properties' =>
                    array (
                        'is_featured' =>
                            array (
                                'null_value' => false,
                                'type' => 'boolean',
                            ),
                        'name-en' =>
                            array (
                                'type' => 'string',
                            ),
                        'name-default' =>
                            array (
                                'type' => 'string',
                                'index' => 'not_analyzed',
                            ),
                        'type' =>
                            array (
                                'type' => 'nested',
                                'properties' =>
                                    array (
                                        'id' =>
                                            array (
                                                'type' => 'integer',
                                            ),
                                    ),
                            ),
                        'categories' =>
                            array (
                                'type' => 'nested',
                                'properties' =>
                                    array (
                                        'id' =>
                                            array (
                                                'type' => 'integer',
                                            ),
                                    ),
                            ),
                        '_id' =>
                            array (
                                'type' => 'string',
                            ),
                        '_score' =>
                            array (
                                'type' => 'float',
                            ),
                        '_parent' =>
                            array (
                                'type' => 'string',
                            ),
                    ),
                'fields' => array (),
                'propertiesMetadata' =>
                    array (
                        'is_featured' =>
                            array (
                                'propertyName' => 'isFeatured',
                                'type' => 'boolean',
                                'multilanguage' => null,
                                'propertyAccess' => 1,
                            ),
                        'type' =>
                            array (
                                'propertyName' => 'type',
                                'type' => 'nested',
                                'multilanguage' => null,
                                'multiple' => null,
                                'propertiesMetadata' =>
                                    array (
                                        'id' =>
                                            array (
                                                'propertyName' => 'id',
                                                'type' => 'integer',
                                                'multilanguage' => null,
                                                'propertyAccess' => 1,
                                            ),
                                    ),
                                'className' => 'AppBundle\\ElasticSearch\\Document\\ObjType',
                                'propertyAccess' => 1,
                            ),
                        'categories' =>
                            array (
                                'propertyName' => 'categories',
                                'type' => 'nested',
                                'multilanguage' => null,
                                'multiple' => true,
                                'propertiesMetadata' =>
                                    array (
                                        'id' =>
                                            array (
                                                'propertyName' => 'id',
                                                'type' => 'integer',
                                                'multilanguage' => null,
                                                'propertyAccess' => 1,
                                            ),
                                    ),
                                'className' => 'AppBundle\\ElasticSearch\\Document\\ObjCategory',
                                'propertyAccess' => 1,
                            ),
                        '_id' =>
                            array (
                                'propertyName' => 'id',
                                'type' => 'string',
                                'multilanguage' => null,
                                'propertyAccess' => 1,
                            ),
                        '_score' =>
                            array (
                                'propertyName' => 'score',
                                'type' => 'float',
                                'multilanguage' => null,
                                'propertyAccess' => 1,
                            ),
                        '_parent' =>
                            array (
                                'propertyName' => 'parent',
                                'type' => 'string',
                                'multilanguage' => null,
                                'propertyAccess' => 1,
                            ),
                    ),
                'objects' =>
                    array (
                        0 => 'AppBundle\\ElasticSearch\\Document\\ObjType',
                        1 => 'AppBundle\\ElasticSearch\\Document\\ObjCategory',
                    ),
                'repositoryClass' => null,
                'className' => 'AppBundle\\ElasticSearch\\Document\\Product',
                'shortClassName' => 'AppBundle:Product',
            )
        );

        $documentMetadataCollectorMock->expects($this->once())->method('getDocumentMetadata')->willReturn($metadata);

        $rawData = array (
            '_index' => 'real_index_123',
            '_type' => 'product',
            '_id' => '5',
            '_version' => 1,
            'found' => true,
            '_source' =>
                array (
                    'is_featured' => false,
                    'name-en' => 'MyProd',
                    'name-default' => 'MyProd',
                    'type' => // single object
                        array (
                            'id' => 13,
                        ),
                    'categories' => // multiple objects
                        array (
                            0 =>
                                array (
                                    'id' => 15,
                                ),
                            1 =>
                                array (
                                    'id' => 89,
                                ),
                        ),
                ),
        );


        $converter = new DocumentConverter($documentMetadataCollectorMock, '-');

        $doc = $converter->convertToDocument($rawData, 'AppBundle:Foo');

        $this->assertInstanceOf('DocumentInterface', $doc);
    }
}
*/
