<?php
/**
 * prooph (http://getprooph.org/)
 *
 * @see       https://github.com/prooph/event-store-symfony-bundle for the canonical source repository
 * @copyright Copyright (c) 2016 - 2019 Alexander Miertsch <kontakt@codeliner.ws>
 * @license   https://github.com/prooph/event-store-symfony-bundle/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ProophTest\Bundle\EventStore;

use PHPUnit\Framework\TestCase;
use Prooph\Bundle\EventStore\DependencyInjection\Compiler\DeprecateFqcnProjectionsPass;
use Prooph\Bundle\EventStore\DependencyInjection\Compiler\ProjectionOptionsPass;
use Prooph\Bundle\EventStore\DependencyInjection\Compiler\RegisterProjectionsPass;
use Prooph\Bundle\EventStore\ProophEventStoreBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class BundleTest extends TestCase
{
    /**
     * @test
     */
    public function it_builds_compiler_pass()
    {
        $container = new ContainerBuilder();
        $bundle = new ProophEventStoreBundle();
        $bundle->build($container);

        $config = $container->getCompilerPassConfig();
        $passes = $config->getBeforeOptimizationPasses();

        self::assertPassRegistered($passes, RegisterProjectionsPass::class);
        self::assertPassRegistered($passes, ProjectionOptionsPass::class);
        self::assertPassRegistered($passes, DeprecateFqcnProjectionsPass::class);
    }

    private static function assertPassRegistered(array $passes, string $class): void
    {
        $passFound = false;
        foreach ($passes as $pass) {
            if ($pass instanceof $class) {
                $passFound = true;
            }
        }

        self::assertTrue($passFound, \sprintf('%s was not found', $class));
    }
}
