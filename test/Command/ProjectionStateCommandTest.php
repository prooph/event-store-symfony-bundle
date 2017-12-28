<?php

declare(strict_types=1);

namespace ProophTest\Bundle\EventStore\Command;

use ProophTest\Bundle\EventStore\Command\Fixture\TestKernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \Prooph\Bundle\EventStore\Command\ProjectionStateCommand
 */
class ProjectionStateCommandTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    /** @test */
    public function it_echoes_the_state_of_a_projection(): void
    {
        $kernel = static::createKernel();
        $kernel->boot();

        $app = new Application($kernel);
        $commandTester = new CommandTester($app->find('event-store:projection:state'));
        $commandTester->execute([
            'projection-name' => 'black_hole_projection'
        ]);
        $this->assertContains('black_hole_projection', $commandTester->getDisplay());
        $this->assertContains('Current status', $commandTester->getDisplay());
        $this->assertContains('Current state', $commandTester->getDisplay());
    }
}
