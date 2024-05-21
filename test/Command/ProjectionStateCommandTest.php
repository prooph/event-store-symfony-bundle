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

use ProophTest\Bundle\EventStore\Command\Fixture\TestKernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @covers \Prooph\Bundle\EventStore\Command\ProjectionStateCommand
 * @covers \Prooph\Bundle\EventStore\Command\AbstractProjectionCommand
 */
class ProjectionStateCommandTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    /**
     * @test
     * @dataProvider provideProjectionNames
     */
    public function it_echoes_the_state_of_a_projection(string $projectionName): void
    {
        $kernel = static::createKernel();
        $kernel->boot();

        $app = new Application($kernel);
        $commandTester = new CommandTester($app->find('event-store:projection:state'));
        $commandTester->execute(['projection-name' => $projectionName]);
        self::assertStringContainsString($projectionName, $commandTester->getDisplay());
        self::assertStringContainsString('Current status', $commandTester->getDisplay());
        self::assertStringContainsString('Current state', $commandTester->getDisplay());
    }

    public static function provideProjectionNames(): array
    {
        return [
            'projection' => ['black_hole_projection'],
            'read_model_projection' => ['black_hole_read_model_projection'],
        ];
    }
}
