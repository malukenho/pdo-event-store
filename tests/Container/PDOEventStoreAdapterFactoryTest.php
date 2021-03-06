<?php
/**
 * This file is part of the prooph/pdo-event-store.
 * (c) 2016-2016 prooph software GmbH <contact@prooph.de>
 * (c) 2016-2016 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ProophTest\EventStore\PDO\Container;

use Interop\Container\ContainerInterface;
use PHPUnit_Framework_TestCase as TestCase;
use Prooph\Common\Messaging\FQCNMessageFactory;
use Prooph\Common\Messaging\MessageConverter;
use Prooph\Common\Messaging\MessageFactory;
use Prooph\Common\Messaging\NoOpMessageConverter;
use Prooph\EventStore\PDO\IndexingStrategy;
use Prooph\EventStore\PDO\JsonQuerier\MySQL;
use Prooph\EventStore\PDO\PDOEventStoreAdapter;
use Prooph\EventStore\PDO\Container\PDOEventStoreAdapterFactory;
use Prooph\EventStore\PDO\TableNameGeneratorStrategy;
use ProophTest\EventStore\PDO\TestUtil;

final class PDOEventStoreAdapterFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_adapter_via_connection_service(): void
    {
        $config['prooph']['event_store']['default']['adapter']['options'] = [
            'connection_service' => 'my_connection',
            'json_querier' => MySQL::class,
            'indexing_strategy' => IndexingStrategy\MySQLAggregateStreamStrategy::class,
        ];

        $connection = TestUtil::getConnection();

        $container = $this->prophesize(ContainerInterface::class);

        $container->get('my_connection')->willReturn($connection)->shouldBeCalled();
        $container->get('config')->willReturn($config)->shouldBeCalled();
        $container->has(MessageFactory::class)->willReturn(false)->shouldBeCalled();
        $container->has(MessageConverter::class)->willReturn(false)->shouldBeCalled();
        $container->get(MySQL::class)->willReturn(new MySQL())->shouldBeCalled();
        $container->get(IndexingStrategy\MySQLAggregateStreamStrategy::class)->willReturn(new IndexingStrategy\MySQLAggregateStreamStrategy())->shouldBeCalled();
        $container->get(TableNameGeneratorStrategy\Sha1::class)->willReturn(new TableNameGeneratorStrategy\Sha1())->shouldBeCalled();

        $factory = new PDOEventStoreAdapterFactory();
        $adapter = $factory($container->reveal());

        $this->assertInstanceOf(PDOEventStoreAdapter::class, $adapter);
    }

    /**
     * @test
     */
    public function it_creates_adapter_via_connection_options(): void
    {
        $config['prooph']['event_store']['custom']['adapter']['options'] = [
            'connection_options' => TestUtil::getConnectionParams(),
            'json_querier' => MySQL::class,
            'indexing_strategy' => IndexingStrategy\MySQLAggregateStreamStrategy::class,
        ];

        $container = $this->prophesize(ContainerInterface::class);

        $container->get('config')->willReturn($config)->shouldBeCalled();
        $container->has(MessageFactory::class)->willReturn(true)->shouldBeCalled();
        $container->get(MessageFactory::class)->willReturn(new FQCNMessageFactory())->shouldBeCalled();
        $container->has(MessageConverter::class)->willReturn(true)->shouldBeCalled();
        $container->get(MessageConverter::class)->willReturn(new NoOpMessageConverter())->shouldBeCalled();
        $container->get(MySQL::class)->willReturn(new MySQL())->shouldBeCalled();
        $container->get(IndexingStrategy\MySQLAggregateStreamStrategy::class)->willReturn(new IndexingStrategy\MySQLAggregateStreamStrategy())->shouldBeCalled();
        $container->get(TableNameGeneratorStrategy\Sha1::class)->willReturn(new TableNameGeneratorStrategy\Sha1())->shouldBeCalled();

        $eventStoreName = 'custom';
        $adapter = PDOEventStoreAdapterFactory::$eventStoreName($container->reveal());

        $this->assertInstanceOf(PDOEventStoreAdapter::class, $adapter);
    }
}
