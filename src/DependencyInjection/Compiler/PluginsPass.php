<?php
/**
 * prooph (http://getprooph.org/)
 *
 * @see       https://github.com/prooph/event-store-symfony-bundle for the canonical source repository
 * @copyright Copyright (c) 2016 - 2019 Alexander Miertsch <kontakt@codeliner.ws>
 * @license   https://github.com/prooph/event-store-symfony-bundle/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Prooph\Bundle\EventStore\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PluginsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
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
