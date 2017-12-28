<?php

declare(strict_types=1);

namespace ProophTest\Bundle\EventStore\Command;

use Prooph\EventStore\Projection\InMemoryProjectionManager;
use ProophTest\Bundle\EventStore\Command\Fixture\TestKernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \Prooph\Bundle\EventStore\Command\ProjectionNamesCommand
 */
class ProjectionNamesCommandTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    /** @test */
    public function it_lists_all_projections(): void
    {
        $kernel = static::createKernel();
        $kernel->boot();

        /** @var InMemoryProjectionManager $manager */
        $manager = $kernel->getContainer()->get('prooph_event_store.projection_manager.main_projection_manager');
        $manager->createProjection('black_hole_projection');

        $app = new Application($kernel);
        $commandTester = new CommandTester($app->find('event-store:projection:names'));
        $commandTester->execute([]);
        $this->assertContains('main_projection_manager', $commandTester->getDisplay());
        $this->assertContains('black_hole_projection', $commandTester->getDisplay());
    }
}
