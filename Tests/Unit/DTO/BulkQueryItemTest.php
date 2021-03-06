<?php

namespace Sineflow\ElasticsearchBundle\Tests\Unit\DTO;
use Sineflow\ElasticsearchBundle\DTO\BulkQueryItem;

/**
 * Class BulkQueryItemTest
 */
class BulkQueryItemTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function getLinesProvider()
    {
        return [
            [
                ['index', 'myindex', 'mytype', ['_id' => '3', '_parent' => 5, 'foo' => 'bar'], false],
                [
                    [
                        'index' => [
                            '_index' => 'myindex',
                            '_type' => 'mytype',
                            '_id' => 3,
                            '_parent' => 5
                        ]
                    ],
                    [
                        'foo' => 'bar'
                    ]
                ]
            ],

            [
                ['create', 'myindex', 'mytype', [], false],
                [
                    [
                        'create' => [
                            '_index' => 'myindex',
                            '_type' => 'mytype',
                        ]
                    ],
                    []
                ]
            ],

            [
                ['update', 'myindex', 'mytype', ['_id' => '3'], 'forcedindex'],
                [
                    [
                        'update' => [
                            '_index' => 'forcedindex',
                            '_type' => 'mytype',
                            '_id' => 3,
                        ]
                    ],
                    []
                ]
            ],

            [
                ['delete', 'myindex', 'mytype', ['_id' => '3'], false],
                [
                    [
                        'delete' => [
                            '_index' => 'myindex',
                            '_type' => 'mytype',
                            '_id' => 3,
                        ]
                    ],
                ]
            ],

        ];
    }


    /**
     * @param array $input
     * @param array $expected
     *
     * @dataProvider getLinesProvider
     */
    public function testGetLines($input, $expected)
    {
        $bqi = new BulkQueryItem($input[0], $input[1], $input[2], $input[3]);
        $lines = $bqi->getLines($input[4]);
        $this->assertEquals($expected, $lines);
    }
}
