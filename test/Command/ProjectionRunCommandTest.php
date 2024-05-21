<?php

/**
 * This file is part of prooph/event-store-symfony-bundle.
 * (c) 2014-2024 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2024 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
 * @covers \Prooph\Bundle\EventStore\Command\AbstractProjectionCommand
 */
class ProjectionRunCommandTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    /**
     * @test
     * @dataProvider provideProjectionNames
     */
    public function it_runs_a_projection(string $projectionName): void
    {
        $kernel = static::createKernel();
        $kernel->boot();

        /** @var InMemoryEventStore $store */
        $store = $kernel->getContainer()->get(InMemoryEventStore::class);
        $store->create(new Stream(new StreamName('main_stream'), new ArrayIterator([new SomethingWasDone([])])));

        /** @var InMemoryProjectionManager $manager */
        $manager = $kernel->getContainer()->get('test.prooph_event_store.projection_manager.main_projection_manager');

        $app = new Application($kernel);
        $commandTester = new CommandTester($app->find('event-store:projection:run'));
        $commandTester->execute(['projection-name' => $projectionName, '--run-once' => true]);

        self::assertSame(
            ['main_stream' => 1],
            $manager->fetchProjectionStreamPositions($projectionName)
        );
    }

    public static function provideProjectionNames(): array
    {
        return [
            'projection' => ['black_hole_projection'],
            'read_model_projection' => ['black_hole_read_model_projection'],
        ];
    }
}
