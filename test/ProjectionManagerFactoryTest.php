<?php

/**
 * This file is part of prooph/event-store-symfony-bundle.
 * (c) 2014-2021 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2021 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\Bundle\EventStore;

use PDO;
use PHPUnit\Framework\TestCase;
use Prooph\Bundle\EventStore\Exception\RuntimeException;
use Prooph\Bundle\EventStore\Factory\ProjectionManagerFactory;
use Prooph\Common\Messaging\MessageFactory;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\EventStoreDecorator;
use Prooph\EventStore\InMemoryEventStore;
use Prooph\EventStore\Pdo\MariaDbEventStore;
use Prooph\EventStore\Pdo\MySqlEventStore;
use Prooph\EventStore\Pdo\PersistenceStrategy;
use Prooph\EventStore\Pdo\PostgresEventStore;
use Prooph\EventStore\Pdo\Projection\MariaDbProjectionManager;
use Prooph\EventStore\Pdo\Projection\MySqlProjectionManager;
use Prooph\EventStore\Pdo\Projection\PostgresProjectionManager;
use Prooph\EventStore\Projection\InMemoryProjectionManager;

class ProjectionManagerFactoryTest extends TestCase
{
    private ProjectionManagerFactory $sut;

    protected function setUp(): void
    {
        $this->sut = new ProjectionManagerFactory();
    }

    /**
     * @test
     */
    public function it_should_not_accept_an_unknown_event_store(): void
    {
        $unknownEventStore = $this->getMockForAbstractClass(EventStore::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(\sprintf(
            'ProjectionManager for %s not implemented.',
            \get_class($unknownEventStore)
        ));

        $this->sut->createProjectionManager($unknownEventStore);
    }

    /**
     * @test
     * @dataProvider provideEventStores
     */
    public function it_should_create_a_projection_manager(
        string $expectedProjectionManagerType,
        EventStore $eventStore
    ): void {
        $connection = $this->createAPdoObject();
        $projectionManager = $this->sut->createProjectionManager($eventStore, $connection);

        self::assertInstanceOf($expectedProjectionManagerType, $projectionManager);
    }

    /**
     * @test
     */
    public function it_cannot_create_a_pdo_manager_without_pdo_connection(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('PDO connection missing');

        $eventStore = $this->createAnEventStore(PostgresEventStore::class);
        $this->sut->createProjectionManager($eventStore);
    }

    public function provideEventStores(): array
    {
        $postgresEventStore = $this->createAnEventStore(PostgresEventStore::class);
        $singleLevelEventStoreDecorator = $this->createAnEventStoreDecorator($postgresEventStore);
        $multiLevelEventStoreDecorator = $this->createAnEventStoreDecorator($singleLevelEventStoreDecorator);

        $eventStores = [
            'InMemoryEventStore' => [
                InMemoryProjectionManager::class,
                $this->createAnEventStore(InMemoryEventStore::class),
            ],
            'PostgresEventStore' => [
                PostgresProjectionManager::class,
                $postgresEventStore,
            ],
            'MySqlEventStore' => [
                MySqlProjectionManager::class,
                $this->createAnEventStore(MySqlEventStore::class),
            ],
            'Single level EventStoreDecorator' => [
                PostgresProjectionManager::class,
                $singleLevelEventStoreDecorator,
            ],
            'Multi level InMemoryEventStore' => [
                PostgresProjectionManager::class,
                $multiLevelEventStoreDecorator,
            ],
        ];

        if (\class_exists(MariaDbEventStore::class)) {
            $eventStores['MariaDbEventStore'] = [
                MariaDbProjectionManager::class,
                $this->createAnEventStore(MariaDbEventStore::class),
            ];
        }

        return $eventStores;
    }

    private function createAnEventStore(string $type): EventStore
    {
        if (InMemoryEventStore::class === $type) {
            return new InMemoryEventStore();
        }

        return new $type(
            $this->createAMessageFactory(),
            $this->createAPdoObject(),
            $this->createAPersistenceStrategy()
        );
    }

    private function createAMessageFactory(): MessageFactory
    {
        return $this->getMockForAbstractClass(MessageFactory::class);
    }

    private function createAPdoObject(): PDO
    {
        return $this->createMock(PDO::class);
    }

    private function createAPersistenceStrategy(): PersistenceStrategy
    {
        return $this->getMockForAbstractClass(PersistenceStrategy::class);
    }

    private function createAnEventStoreDecorator(EventStore $decoratedEventStore): EventStoreDecorator
    {
        $eventStoreDecorator = $this->getMockForAbstractClass(EventStoreDecorator::class);
        $eventStoreDecorator
            ->method('getInnerEventStore')
            ->willReturn($decoratedEventStore);

        return $eventStoreDecorator;
    }
}
