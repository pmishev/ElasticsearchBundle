<?php

namespace Sineflow\ElasticsearchBundle\Tests\Unit\Annotation;

use Sineflow\ElasticsearchBundle\Annotation\Document;

/**
 * Class DocumentTest
 */
class DocumentTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests if values are dumped correctly
     */
    public function testDump()
    {
        $doc = new Document();

        $doc->type = 'product';
        $doc->options = [
            'dynamic' => 'strict',
            'foo' => 'bar',
        ];

        $this->assertEquals(
            [
                'dynamic' => 'strict',
                'foo' => 'bar',
            ],
            $doc->dump(),
            'All and only options should be dumped'
        );
    }
}
