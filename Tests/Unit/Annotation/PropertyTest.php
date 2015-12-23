<?php

namespace Sineflow\ElasticsearchBundle\Tests\Unit\Annotation;

use Sineflow\ElasticsearchBundle\Annotation\Property;

/**
 * Class PropertyTest
 */
class PropertyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests if values are dumped correctly for mapping.
     */
    public function testDump()
    {
        $type = new Property();

        $type->name = 'myprop';
        $type->type = 'mytype';
        $type->multilanguage = false;
        $type->objectName = 'foo/bar';
        $type->multiple = null;
        $type->options = [
            'type' => 'this should not be set here',
            'analyzer' => 'standard',
            'foo' => 'bar',
        ];
        $type->foo = 'bar';

        $this->assertEquals(
            [
                'analyzer' => 'standard',
                'foo' => 'bar',
                'type' => 'mytype',
            ],
            $type->dump(),
            'Properties should be filtered'
        );
    }
}
