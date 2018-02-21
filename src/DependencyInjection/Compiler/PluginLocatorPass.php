<?php

declare(strict_types=1);

namespace Prooph\Bundle\EventStore\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class PluginLocatorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (! $container->hasParameter('prooph_event_store.stores')) {
            return;
        }

        $stores = $container->getParameter('prooph_event_store.stores');

        $globalPlugins = $container->findTaggedServiceIds('prooph_event_store.plugin');
        $storePlugins = [];

        foreach ($stores as $name => $store) {
            $storePlugins[] = $container->findTaggedServiceIds(sprintf('prooph_event_store.%s.plugin', $name));
        }

        $plugins = array_merge($globalPlugins, ...$storePlugins);

        $locatorPlugins = [];

        foreach ($plugins as $id => $attributes) {
            $locatorPlugins[$id] = new ServiceClosureArgument(new Reference($id));
        }

        $container
            ->setDefinition(
                'prooph_event_store.plugins_locator',
                new Definition(ServiceLocator::class, [$locatorPlugins])
            )
            ->addTag('container.service_locator');
    }
}
