<?php

declare(strict_types=1);

namespace ProophTest\Bundle\EventStore\Command;

use Prooph\EventStore\Exception\RuntimeException;
use ProophTest\Bundle\EventStore\Command\Fixture\TestKernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \Prooph\Bundle\EventStore\Command\ProjectionStopCommand
 * @covers \Prooph\Bundle\EventStore\Command\AbstractProjectionCommand
 */
class ProjectionStopCommandTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    /**
     * @test
     * @dataProvider provideProjectionNames
     */
    public function it_stops_a_projection(string $projectionName): void
    {
        $kernel = static::createKernel();
        $kernel->boot();

        $app = new Application($kernel);
        $commandTester = new CommandTester($app->find('event-store:projection:stop'));
        try {
            $commandTester->execute(['projection-name' => $projectionName]);
        } catch (RuntimeException $notSupported) {
            $this->assertContains('Stopping a projection is not supported', $notSupported->getMessage());

            return;
        }
        $this->fail('The projection was not stopped');
    }

    public static function provideProjectionNames(): array
    {
        return [
            'projection' => ['black_hole_projection'],
            'read_model_projection' => ['black_hole_read_model_projection'],
        ];
    }
}
