<?php

declare(strict_types=1);

namespace Prooph\Bundle\EventStore\Factory;

use Prooph\EventStore\EventStore;
use Prooph\EventStore\Exception\RuntimeException;
use Prooph\EventStore\Plugin\Plugin;
use Psr\Container\ContainerInterface;

class DefaultEventStoreFactory implements EventStoreFactory
{
    public function createEventStore(
        string $eventStoreName,
        EventStore $eventStore,
        ActionEventEmitterFactory $actionEventEmitterFactory,
        string $actionEventEmitter,
        bool $wrapActionEventEmitter,
        ContainerInterface $container,
        array $plugins = []
    ): EventStore {

        if ($wrapActionEventEmitter === false) {
            return $eventStore;
        }

        $actionEventEmittingEventStore = $actionEventEmitterFactory::create($eventStore, $actionEventEmitter);

        foreach ($plugins as $pluginAlias) {
            $plugin = $container->get($pluginAlias);

            if (!$plugin instanceof Plugin) {
                throw new RuntimeException(sprintf(
                    'Plugin %s does not implement the Plugin interface',
                    $pluginAlias
                ));
            }

            $plugin->attachToEventStore($actionEventEmittingEventStore);
        }


        if ($this->hasMetadataEnricherPlugin($eventStoreName, $container)) {
            $this->getMetadataEnricherPlugin($eventStoreName, $container)->attachToEventStore($actionEventEmittingEventStore);
        }

        return $actionEventEmittingEventStore;
    }

    /**
     * @param string             $eventStoreName
     * @param ContainerInterface $container
     *
     * @return Plugin
     */
    private function getMetadataEnricherPlugin(string $eventStoreName, ContainerInterface $container): Plugin
    {
        $metadataEnricherId = $this->buildMetadataEnricherIdForStore($eventStoreName);

        /** @var Plugin $metadataEnricherPlugin */
        $metadataEnricherPlugin = $container->get($metadataEnricherId);

        return $metadataEnricherPlugin;
    }

    /**
     * @param string             $eventStoreName The container id of the concrete event store
     * @param ContainerInterface $container
     *
     * @return bool
     */
    private function hasMetadataEnricherPlugin(string $eventStoreName, ContainerInterface $container): bool
    {
        return $container->has($this->buildMetadataEnricherIdForStore($eventStoreName));
    }

    /**
     * @param string $eventStoreName Short name from configuration
     *
     * @return string
     */
    private function buildMetadataEnricherIdForStore(string $eventStoreName): string
    {
        return sprintf('prooph_event_store.metadata_enricher_plugin.%s', $eventStoreName);
    }

}
