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

namespace Prooph\Bundle\EventStore\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PluginsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasParameter('prooph_event_store.stores')) {
            return;
        }

        $stores = $container->getParameter('prooph_event_store.stores');

        foreach ($stores as $name => $store) {
            $globalPlugins = $container->findTaggedServiceIds('prooph_event_store.plugin');
            $storePlugins = $container->findTaggedServiceIds(\sprintf('prooph_event_store.%s.plugin', $name));

            $plugins = \array_merge($globalPlugins, $storePlugins);

            $eventStoreDefinition = $container->findDefinition(\sprintf('prooph_event_store.%s', $name));

            $eventStoreDefinition->addArgument(\array_keys($plugins));
        }
    }
}
