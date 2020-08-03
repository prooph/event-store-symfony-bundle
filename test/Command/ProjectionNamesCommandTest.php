<?php

declare(strict_types=1);

namespace ProophTest\Bundle\EventStore\Command;

use Prooph\EventStore\Projection\InMemoryProjectionManager;
use ProophTest\Bundle\EventStore\Command\Fixture\TestKernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \Prooph\Bundle\EventStore\Command\AbstractProjectionCommand
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
        $manager = $kernel->getContainer()->get('test.prooph_event_store.projection_manager.main_projection_manager');
        $manager->createProjection($projectionName);

        $app = new Application($kernel);
        $commandTester = new CommandTester($app->find('event-store:projection:names'));
        $commandTester->execute([]);
        self::assertContains('main_projection_manager', $commandTester->getDisplay());
        self::assertContains($projectionName, $commandTester->getDisplay());
    }

    public static function provideProjectionNames(): array
    {
        return [
            'projection' => ['black_hole_projection'],
            'read_model_projection' => ['black_hole_read_model_projection'],
        ];
    }

    /**
     * @test
     * @dataProvider provideLimitOptions
     */
    public function it_lists_correct_amount_of_projections(int $amountToGenerate, int $limit = null): void
    {
        $kernel = static::createKernel();
        $kernel->boot();

        /** @var InMemoryProjectionManager $manager */
        $manager = $kernel->getContainer()->get('test.prooph_event_store.projection_manager.main_projection_manager');

        $projectionNames = $this->projectionNameGenerator($amountToGenerate);

        foreach ($projectionNames as $projectionName) {
            $manager->createProjection($projectionName);
        }

        $app = new Application($kernel);
        $command = $app->find('event-store:projection:names');

        if (null === $limit) {
            $limit = $command->getDefinition()->getOption('limit')->getDefault();
        }

        $commandTester = new CommandTester($command);
        $commandTester->execute(['--limit' => $limit]);
        self::assertContains('main_projection_manager', $commandTester->getDisplay());

        $expectedProjectionNames = \array_slice($projectionNames, 0, $limit);
        $unexpectedProjectionNames = \array_slice($projectionNames, $limit);
        foreach ($expectedProjectionNames as $projectionName) {
            self::assertContains($projectionName, $commandTester->getDisplay());
        }
        foreach ($unexpectedProjectionNames as $projectionName) {
            self::assertNotContains($projectionName, $commandTester->getDisplay());
        }
    }

    /**
     * Generates an array of projection names, each suffixed with a zero-padded number
     *
     * Example:
     *     [
     *         'black_hole_read_model_projection_01',
     *         'black_hole_read_model_projection_02',
     *         'black_hole_read_model_projection_03',
     *         'black_hole_read_model_projection_04',
     *         'black_hole_read_model_projection_05',
     *         'black_hole_read_model_projection_06',
     *         'black_hole_read_model_projection_07',
     *         'black_hole_read_model_projection_08',
     *         'black_hole_read_model_projection_09',
     *         'black_hole_read_model_projection_10',
     *         'black_hole_read_model_projection_11',
     *         ...
     *     ]
     *
     * @param int $amountToGenerate
     * @param string $prefix
     */
    private function projectionNameGenerator(
        int $amountToGenerate,
        string $prefix = 'black_hole_read_model_projection_'
    ): array {
        return \array_map(
            function ($value) use ($amountToGenerate, $prefix) {
                return $prefix . \str_pad((string) $value, \strlen((string) $amountToGenerate), '0', \STR_PAD_LEFT);
            },
            \range(1, $amountToGenerate)
        );
    }

    public function provideLimitOptions(): array
    {
        return [
            [
                25,
                null,
            ],
            [
                25,
                5,
            ],
            [
                25,
                25,
            ],
        ];
    }
}
