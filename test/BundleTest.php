<?php
/**
 * prooph (http://getprooph.org/)
 *
 * @see       https://github.com/prooph/event-store-symfony-bundle for the canonical source repository
 * @copyright Copyright (c) 2016 prooph software GmbH (http://prooph-software.com/)
 * @license   https://github.com/prooph/event-store-symfony-bundle/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ProophTest\Bundle\EventStore;

use PHPUnit\Framework\TestCase;
use Prooph\Bundle\EventStore\DependencyInjection\Compiler\MetadataEnricherPass;
use Prooph\Bundle\EventStore\DependencyInjection\Compiler\PluginsPass;
use Prooph\Bundle\EventStore\ProophEventStoreBundle;
use ProophTest\Bundle\EventStore\DependencyInjection\Fixture\Plugin\GlobalBlackHole;
use ProophTest\Bundle\EventStore\DependencyInjection\Fixture\TestServices;
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

        $foundPluginPass = false;
        $foundMetadataEnricherPass = false;

        foreach ($passes as $pass) {
            if ($pass instanceof PluginsPass) {
                $foundPluginPass = true;
            } elseif ($pass instanceof MetadataEnricherPass) {
                $foundMetadataEnricherPass = true;
            }
        }

        self::assertTrue($foundPluginPass, 'PluginsPass was not found');
        self::assertTrue($foundMetadataEnricherPass, 'MetadataEnricherPass was not found');
    }
}
