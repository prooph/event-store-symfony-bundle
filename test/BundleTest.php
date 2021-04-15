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

namespace ProophTest\Bundle\EventStore;

use PHPUnit\Framework\TestCase;
use Prooph\Bundle\EventStore\DependencyInjection\Compiler\MetadataEnricherPass;
use Prooph\Bundle\EventStore\DependencyInjection\Compiler\PluginsPass;
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
