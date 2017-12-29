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

    /**
     * @test
     * @dataProvider provideProjectionNames
     */
    public function it_lists_all_projections(string $projectionName): void
    {
        $kernel = static::createKernel();
        $kernel->boot();

        /** @var InMemoryProjectionManager $manager */
        $manager = $kernel->getContainer()->get('prooph_event_store.projection_manager.main_projection_manager');
        $manager->createProjection($projectionName);

        $app = new Application($kernel);
        $commandTester = new CommandTester($app->find('event-store:projection:names'));
        $commandTester->execute([]);
        $this->assertContains('main_projection_manager', $commandTester->getDisplay());
        $this->assertContains($projectionName, $commandTester->getDisplay());
    }

    public static function provideProjectionNames(): array
    {
        return [
            'projection' => ['black_hole_projection'],
            'read_model_projection' => ['black_hole_read_model_projection'],
        ];
    }
}
