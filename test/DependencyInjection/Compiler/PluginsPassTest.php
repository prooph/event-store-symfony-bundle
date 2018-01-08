<?php

declare(strict_types=1);

namespace ProophTest\Bundle\EventStore\DependencyInjection\Compiler;

use Prooph\Bundle\EventStore\DependencyInjection\Compiler\PluginsPass;
use ProophTest\Bundle\EventStore\DependencyInjection\Fixture\Plugin\BlackHole;
use ProophTest\Bundle\EventStore\DependencyInjection\Fixture\Plugin\GlobalBlackHole;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class PluginsPassTest extends CompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container)
    {
        $container->addCompilerPass(new PluginsPass());
    }

    /**
     * @test
     */
    public function it_registers_plugins()
    {
        $this->registerEventStore('foo');
        $this->registerEventStore('bar');

        $this->registerPlugin(null, GlobalBlackHole::class, null);
        $this->registerPlugin('foo', BlackHole::class, null);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'prooph_event_store.foo',
            0,
            [
                GlobalBlackHole::class,
                BlackHole::class,
            ]
        );

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'prooph_event_store.bar',
            0,
            [
                GlobalBlackHole::class,
            ]
        );
    }

    private function registerPlugin(?string $storeName, string $className, ?string $serviceId = null): Definition
    {
        $pluginDefinition = new Definition($className);
        $pluginDefinition->addTag(
            sprintf('prooph_event_store.%splugin', $storeName ? $storeName . '.' : '')
        );
        $this->setDefinition($serviceId ?? $className, $pluginDefinition);

        return $pluginDefinition;
    }
}
