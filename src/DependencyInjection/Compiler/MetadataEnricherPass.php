<?php

/**
 * This file is part of prooph/event-store-symfony-bundle.
 * (c) 2014-2022 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2022 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\Bundle\EventStore\DependencyInjection\Compiler;

use Prooph\EventStore\Metadata\MetadataEnricherPlugin;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class MetadataEnricherPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasParameter('prooph_event_store.stores')) {
            return;
        }

        $stores = $container->getParameter('prooph_event_store.stores');

        $globalPlugins = $container->findTaggedServiceIds('prooph_event_store.metadata_enricher');

        foreach ($stores as $name => $store) {
            $storeEnricherPlugins = $container->findTaggedServiceIds(\sprintf('prooph_event_store.%s.metadata_enricher', $name));

            /** @var array<string, string> $plugins */
            $plugins = \array_merge($globalPlugins, $storeEnricherPlugins);
            $enrichers = [];

            foreach ($plugins as $id => $args) {
                $enrichers[] = new Reference($id);
            }

            $metadataEnricherAggregateId = \sprintf('prooph_event_store.%s.%s', 'metadata_enricher_aggregate', $name);
            $metadataEnricherAggregateDefinition = $container->getDefinition($metadataEnricherAggregateId);
            $metadataEnricherAggregateDefinition->setArguments([$enrichers]);

            $metadataEnricherId = \sprintf('prooph_event_store.%s.%s', 'metadata_enricher_plugin', $name);
            $metadataEnricherDefinition = $container->getDefinition($metadataEnricherId);
            $metadataEnricherDefinition->setClass(MetadataEnricherPlugin::class);
            $metadataEnricherDefinition->addTag(\sprintf('prooph_event_store.%s.plugin', $name));
            $metadataEnricherDefinition->setArguments([new Reference($metadataEnricherAggregateId)]);
        }
    }
}
