<?php

namespace Sineflow\ElasticsearchBundle\Tests\Functional\Manager;

use Elasticsearch\Common\Exceptions\Missing404Exception;
use Sineflow\ElasticsearchBundle\Exception\Exception;
use Sineflow\ElasticsearchBundle\Manager\IndexManager;
use Sineflow\ElasticsearchBundle\Tests\AbstractElasticsearchTestCase;
use Sineflow\ElasticsearchBundle\Tests\app\fixture\Acme\BarBundle\Document\Product;

/**
 * Class IndexManagerTest
 */
class IndexManagerTest extends AbstractElasticsearchTestCase
{
    public function testGetReadAliasAndGetWriteAlias()
    {
        $imWithAliases = $this->getIndexManager('foo', false);
        $this->assertEquals('sineflow-esb-test', $imWithAliases->getReadAlias());
        $this->assertEquals('sineflow-esb-test_write', $imWithAliases->getWriteAlias());

        $imWithoutAliases = $this->getIndexManager('bar', false);
        $this->assertEquals('sineflow-esb-test-bar', $imWithoutAliases->getReadAlias());
        $this->assertEquals('sineflow-esb-test-bar', $imWithoutAliases->getWriteAlias());
    }

    public function testCreateIndexWithAliases()
    {
        $imWithAliases = $this->getIndexManager('foo', false);
        $imWithAliases->createIndex();

        $this->assertTrue($imWithAliases->getConnection()->existsAlias(array('name' => 'sineflow-esb-test')), 'Read alias does not exist');
        $this->assertTrue($imWithAliases->getConnection()->existsAlias(array('name' => 'sineflow-esb-test_write')), 'Write alias does not exist');

        $indicesPointedByAliases = $imWithAliases->getConnection()->getClient()->indices()->getAlias(['name' => 'sineflow-esb-test,sineflow-esb-test_write']);
        $this->assertCount(1, $indicesPointedByAliases, 'Read and Write aliases must point to one and the same index');
    }

    public function testCreateIndexWithoutAliases()
    {
        $imWithoutAliases = $this->getIndexManager('bar', false);
        $imWithoutAliases->createIndex();

        $index = $imWithoutAliases->getConnection()->getClient()->indices()->getAliases(['index' => 'sineflow-esb-test-bar']);
        $this->assertCount(1, $index, 'Index was not created');
        $this->assertCount(0, current($index)['aliases'], 'Index should not have any aliases pointing to it');
    }

    public function testDropIndexWithAliases()
    {
        $imWithAliases = $this->getIndexManager('foo', false);
        $imWithAliases->createIndex();

        // Simulate state during rebuilding when write alias points to more than 1 index
        try {
            $imWithAliases->getConnection()->getClient()->indices()->delete(['index' => 'sineflow-esb-test-temp']);
        } catch (\Exception $e) {
        }
        $imWithAliases->getConnection()->getClient()->indices()->create(['index' => 'sineflow-esb-test-temp']);
        $setAliasParams = [
            'body' => [
                'actions' => [
                    [
                        'add' => [
                            'index' => 'sineflow-esb-test-temp',
                            'alias' => $imWithAliases->getWriteAlias()
                        ],
                    ]
                ],
            ],
        ];
        $imWithAliases->getConnection()->getClient()->indices()->updateAliases($setAliasParams);

        $imWithAliases->dropIndex();

        $this->setExpectedException(Missing404Exception::class);
        $imWithAliases->getConnection()->getClient()->indices()->getAlias(['name' => 'sineflow-esb-test,sineflow-esb-test_write']);
    }

    /**
     * @expectedException Exception
     */
    public function testGetLiveIndexWhenNoIndexExists()
    {
        /** @var IndexManager $imWithAliases */
        $imWithAliases = $this->getIndexManager('foo', false);
        $imWithAliases->getLiveIndex();
    }

    public function testGetLiveIndex()
    {
        /** @var IndexManager $imWithAliases */
        $imWithAliases = $this->getIndexManager('foo');
        $liveIndex = $imWithAliases->getLiveIndex();
        $this->assertRegExp('/^sineflow-esb-test_[0-9_]+$/', $liveIndex);

        /** @var IndexManager $imWithoutAliases */
        $imWithoutAliases = $this->getIndexManager('bar');
        $liveIndex = $imWithoutAliases->getLiveIndex();
        $this->assertEquals('sineflow-esb-test-bar', $liveIndex);
    }

    /**
     * @expectedException Exception
     */
    public function testRebuildIndexWithoutAliases()
    {
        $imWithoutAliases = $this->getIndexManager('bar');
        $imWithoutAliases->rebuildIndex();
    }

    public function testRebuildIndexWithoutDeletingOld()
    {
        $imWithAliases = $this->getIndexManager('foo');
        $liveIndex = $imWithAliases->getLiveIndex();

        $imWithAliases->rebuildIndex();

        $this->assertTrue($imWithAliases->getConnection()->getClient()->indices()->exists(array('index' => $liveIndex)));
        $imWithAliases->getConnection()->getClient()->indices()->delete(['index' => $liveIndex]);

        $newLiveIndex = $imWithAliases->getLiveIndex();
        $this->assertNotEquals($liveIndex, $newLiveIndex);
    }

    public function testRebuildIndexAndDeleteOld()
    {
        $imWithAliases = $this->getIndexManager('foo');
        $liveIndex = $imWithAliases->getLiveIndex();

        $imWithAliases->rebuildIndex(true);

        $this->assertFalse($imWithAliases->getConnection()->getClient()->indices()->exists(array('index' => $liveIndex)));

        $newLiveIndex = $imWithAliases->getLiveIndex();
        $this->assertNotEquals($liveIndex, $newLiveIndex);
    }

}
