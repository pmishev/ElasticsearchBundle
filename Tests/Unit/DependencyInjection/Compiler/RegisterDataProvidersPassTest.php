<?php

namespace Sineflow\ElasticsearchBundle\Tests\Unit\DependencyInjection\Compiler;

use Sineflow\ElasticsearchBundle\DependencyInjection\Compiler\AddIndexManagersPass;
use Sineflow\ElasticsearchBundle\DependencyInjection\Compiler\MappingPass;
use Sineflow\ElasticsearchBundle\DependencyInjection\Compiler\RegisterDataProvidersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Unit tests for AddConnectionsPass.
 */
class RegisterDataProvidersPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Before a test method is run, a template method called setUp() is invoked.
     */
    public function testProcessWithElasticsearchProvider()
    {
        $containerMock = $this->getMockBuilder('\Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $containerMock->method('hasDefinition')->with('sfes.provider_registry')->willReturn(true);

        $containerMock->expects($this->exactly(1))->method('findTaggedServiceIds')->willReturn(
            array (
                'app.es.data_provider.mytype' =>
                    array (
                        0 =>
                            array (
                                'type' => 'AppBundle:MyType',
                            ),
                    ),
                'app.es.data_provider.mytype2' =>
                    array (
                        0 =>
                            array (
                                'type' => 'AppBundle:MyType2',
                            ),
                    ),
            )
        );

        $containerMock->expects($this->exactly(3))->method('getDefinition')->with($this->anything())
            ->will(
                $this->returnCallback(
                    function ($parameter) {
                        switch ($parameter) {
                            case 'sfes.provider_registry':
                                return new Definition('\Sineflow\ElasticsearchBundle\Document\Provider\ProviderRegistry');
                            case 'app.es.data_provider.mytype':
                            case 'app.es.data_provider.mytype2':
                                return new Definition('\Sineflow\ElasticsearchBundle\Document\Provider\ElasticsearchProvider');
                            default:
                                return null;
                        }
                    }
                )
            );

        $compilerPass = new RegisterDataProvidersPass();
        $compilerPass->process($containerMock);
    }

    /**
     * Test registering a provider that does not have a type tag set
     * 
     * @expectedException \InvalidArgumentException
     */
    public function testProcessWithProviderWithoutTypeTag()
    {
        $containerMock = $this->getMockBuilder('\Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $containerMock->method('hasDefinition')->with('sfes.provider_registry')->willReturn(true);

        $containerMock->expects($this->exactly(1))->method('findTaggedServiceIds')->willReturn(
            array (
                'app.es.data_provider.notype' =>
                    array (
                        0 =>
                            array (),
                    ),
            )
        );

        $containerMock->expects($this->exactly(1))->method('getDefinition')->with($this->anything())
            ->will(
                $this->returnCallback(
                    function ($parameter) {
                        switch ($parameter) {
                            case 'sfes.provider_registry':
                                return new Definition('\Sineflow\ElasticsearchBundle\Document\Provider\ProviderRegistry');
                            default:
                                return null;
                        }
                    }
                )
            );

        $compilerPass = new RegisterDataProvidersPass();
        $compilerPass->process($containerMock);
    }
}
