<?php

declare(strict_types=1);

namespace ProophTest\Bundle\EventStore\Command;

use ArrayIterator;
use Prooph\EventStore\InMemoryEventStore;
use Prooph\EventStore\Projection\InMemoryProjectionManager;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;
use ProophTest\Bundle\EventStore\Command\Fixture\Event\SomethingWasDone;
use ProophTest\Bundle\EventStore\Command\Fixture\TestKernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \Prooph\Bundle\EventStore\Command\ProjectionRunCommand
 */
class ProjectionRunCommandTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    /** @test */
    public function it_runs_a_projection(): void
    {
        $kernel = static::createKernel();
        $kernel->boot();

        /** @var InMemoryEventStore $store */
        $store = $kernel->getContainer()->get(InMemoryEventStore::class);
        $store->create(new Stream(new StreamName('main_stream'), new ArrayIterator([new SomethingWasDone([])])));

        /** @var InMemoryProjectionManager $manager */
        $manager = $kernel->getContainer()->get('prooph_event_store.projection_manager.main_projection_manager');

        $app = new Application($kernel);
        $commandTester = new CommandTester($app->find('event-store:projection:run'));
        $commandTester->execute(['projection-name' => 'black_hole_projection', '--run-once' => true]);

        $this->assertSame(
            ['main_stream' => 1],
            $manager->fetchProjectionStreamPositions('black_hole_projection')
        );
    }
}
