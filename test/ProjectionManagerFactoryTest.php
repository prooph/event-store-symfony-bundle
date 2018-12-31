<?php

declare(strict_types=1);

namespace ProophTest\Bundle\EventStore;

use PDO;
use PHPUnit\Framework\TestCase;
use Prooph\Bundle\EventStore\Exception\RuntimeException;
use Prooph\Bundle\EventStore\ProjectionManagerFactory;
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
    /**
     * @var ProjectionManagerFactory
     */
    private $sut;

    protected function setUp()
    {
        $this->sut = new ProjectionManagerFactory();
    }

    /**
     * @test
     */
    public function it_should_not_accept_an_unknown_event_store()
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
    ) {
        $connection = $this->createAPdoObject();
        $projectionManager = $this->sut->createProjectionManager($eventStore, $connection);

        $this->assertInstanceOf($expectedProjectionManagerType, $projectionManager);
    }

    public function provideEventStores(): array
    {
        $postgresEventStore = $this->createAnEventStore(PostgresEventStore::class);
        $singleLevelEventStoreDecorator = $this->createAnEventStoreDecorator($postgresEventStore);
        $multiLevelEventStoreDecorator = $this->createAnEventStoreDecorator($singleLevelEventStoreDecorator);

        return [
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
            'MariaDbEventStore' => [
                MariaDbProjectionManager::class,
                $this->createAnEventStore(MariaDbEventStore::class),
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
        $eventStoreDecorator->expects($this->any())
            ->method('getInnerEventStore')
            ->willReturn($decoratedEventStore);

        return $eventStoreDecorator;
    }
}
