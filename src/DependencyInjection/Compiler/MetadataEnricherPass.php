<?php
/**
 * prooph (http://getprooph.org/)
 *
 * @see       https://github.com/prooph/event-store-symfony-bundle for the canonical source repository
 * @copyright Copyright (c) 2016 prooph software GmbH (http://prooph-software.com/)
 * @license   https://github.com/prooph/event-store-symfony-bundle/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Prooph\Bundle\EventStore\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class MetadataEnricherPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->getParameter('prooph_event_store.stores')) {
            return;
        }

        $stores = $container->getParameter('prooph_event_store.stores');

        foreach ($stores as $name => $store) {
            $globalPlugins = $container->findTaggedServiceIds('prooph_event_store.metadata_enricher');
            $storeEnricherPLugins = $container->findTaggedServiceIds(sprintf('prooph_event_store.%s.metadata_enricher', $name));
            $plugins = array_merge($globalPlugins, $storeEnricherPLugins);
            $enrichers = [];

            foreach ($plugins as $id => $args) {
                $enrichers[] = new Reference($id);
            }

            $metadataEnricherAggregateId = sprintf('prooph_event_store.%s.%s', 'metadata_enricher_aggregate', $name);
            $metadataEnricherAggregateDefinition = $container->getDefinition($metadataEnricherAggregateId);
            $metadataEnricherAggregateDefinition->setArguments([$enrichers]);

            $metadataEnricherId = sprintf('prooph_event_store.%s.%s', 'metadata_enricher_plugin', $name);
            $metadataEnricherDefinition = $container->getDefinition($metadataEnricherId);
            $metadataEnricherDefinition->setClass('%prooph_event_store.metadata_enricher_plugin.class%');
            $metadataEnricherDefinition->setArguments([new Reference($metadataEnricherAggregateId)]);
            $metadataEnricherDefinition->addMethodCall('setUp', [new Reference($store)]);
        }
    }
}
