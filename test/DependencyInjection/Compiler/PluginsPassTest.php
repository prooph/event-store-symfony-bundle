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

namespace ProophTest\Bundle\EventStore\DependencyInjection\Compiler;

use Prooph\Bundle\EventStore\DependencyInjection\Compiler\PluginsPass;
use ProophTest\Bundle\EventStore\DependencyInjection\Fixture\Plugin\BlackHole;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class PluginsPassTest extends CompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new PluginsPass());
    }

    /** @test */
    public function it_registers_plugins()
    {
        $this->registerEventStore('foo');
        $this->registerEventStore('bar');

        $this->registerPlugin(null, BlackHole::class, 'global_plugin');
        $this->registerPlugin('foo', BlackHole::class, null);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'prooph_event_store.foo',
            0,
            [
                'global_plugin',
                BlackHole::class,
            ]
        );

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'prooph_event_store.bar',
            0,
            [
                'global_plugin',
            ]
        );
    }

    private function registerPlugin(?string $storeName, string $className, ?string $serviceId = null): Definition
    {
        $pluginDefinition = new Definition($className);
        $pluginDefinition->addTag(
            \sprintf('prooph_event_store.%splugin', $storeName ? $storeName . '.' : '')
        );
        $this->setDefinition($serviceId ?? $className, $pluginDefinition);

        return $pluginDefinition;
    }
}
