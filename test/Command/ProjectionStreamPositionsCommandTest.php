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

namespace ProophTest\Bundle\EventStore\Command;

use ArrayIterator;
use Prooph\Bundle\EventStore\Projection\Projection;
use Prooph\Bundle\EventStore\Projection\ReadModelProjection;
use Prooph\EventStore\InMemoryEventStore;
use Prooph\EventStore\Projection\InMemoryProjectionManager;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;
use ProophTest\Bundle\EventStore\Command\Fixture\Event\SomethingWasDone;
use ProophTest\Bundle\EventStore\Command\Fixture\Projection\BlackHoleReadModel;
use ProophTest\Bundle\EventStore\Command\Fixture\TestKernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \Prooph\Bundle\EventStore\Command\ProjectionStreamPositionsCommand
 * @covers \Prooph\Bundle\EventStore\Command\AbstractProjectionCommand
 */
class ProjectionStreamPositionsCommandTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    /** @test */
    public function it_echoes_the_position_of_a_projection(): void
    {
        $kernel = static::createKernel();
        $kernel->boot();

        /** @var InMemoryEventStore $store */
        $store = $kernel->getContainer()->get(InMemoryEventStore::class);
        $store->create(new Stream(new StreamName('main_stream'), new ArrayIterator([new SomethingWasDone([])])));

        /** @var Projection $projection */
        $projection = $kernel->getContainer()->get('test.prooph_event_store.projection.black_hole_projection');

        /** @var InMemoryProjectionManager $manager */
        $manager = $kernel->getContainer()->get('test.prooph_event_store.projection_manager.main_projection_manager');

        $projection->project($manager->createProjection('black_hole_projection'))->run(false);

        $app = new Application($kernel);
        $commandTester = new CommandTester($app->find('event-store:projection:positions'));
        $commandTester->execute(['projection-name' => 'black_hole_projection']);
        self::assertStringContainsString('main_stream', $commandTester->getDisplay());
        self::assertStringContainsString(' 1 ', $commandTester->getDisplay());
    }

    /** @test */
    public function it_echoes_the_position_of_a_read_model_projection(): void
    {
        $kernel = static::createKernel();
        $kernel->boot();

        /** @var InMemoryEventStore $store */
        $store = $kernel->getContainer()->get(InMemoryEventStore::class);
        $store->create(new Stream(new StreamName('main_stream'), new ArrayIterator([new SomethingWasDone([])])));

        /** @var ReadModelProjection $projection */
        $projection = $kernel->getContainer()->get('test.prooph_event_store.projection.black_hole_read_model_projection');
        $readModel = $kernel->getContainer()->get(BlackHoleReadModel::class);

        /** @var InMemoryProjectionManager $manager */
        $manager = $kernel->getContainer()->get('test.prooph_event_store.projection_manager.main_projection_manager');

        $projection
            ->project($manager->createReadModelProjection('black_hole_read_model_projection', $readModel))
            ->run(false);

        $app = new Application($kernel);
        $commandTester = new CommandTester($app->find('event-store:projection:positions'));
        $commandTester->execute(['projection-name' => 'black_hole_read_model_projection']);
        self::assertStringContainsString('main_stream', $commandTester->getDisplay());
        self::assertStringContainsString(' 1 ', $commandTester->getDisplay());
    }
}
