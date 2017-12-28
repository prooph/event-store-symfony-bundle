<?php

declare(strict_types=1);

namespace ProophTest\Bundle\EventStore\Command;

use Prooph\EventStore\Exception\RuntimeException;
use ProophTest\Bundle\EventStore\Command\Fixture\TestKernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \Prooph\Bundle\EventStore\Command\ProjectionDeleteCommand
 */
class ProjectionDeleteCommandTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    /** @test */
    public function it_deletes_a_projection()
    {
        $kernel = static::createKernel();
        $kernel->boot();

        $app = new Application($kernel);
        $commandTester = new CommandTester($app->find('event-store:projection:delete'));
        try {
            $commandTester->execute([
                'projection-name' => 'black_hole_projection'
            ]);
        } catch (RuntimeException $notSupported) {
            $this->assertContains('Deleting a projection is not supported', $notSupported->getMessage());
            return;
        }
        $this->fail('The projection was not deleted');
    }
}
