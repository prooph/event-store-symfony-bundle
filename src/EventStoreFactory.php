<?php
/**
 * prooph (http://getprooph.org/)
 *
 * @see       https://github.com/prooph/event-store-symfony-bundle for the canonical source repository
 * @copyright Copyright (c) 2016 prooph software GmbH (http://prooph-software.com/)
 * @license   https://github.com/prooph/event-store-symfony-bundle/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Prooph\Bundle\EventStore;

use Prooph\Common\Event\ActionEventEmitter;
use Prooph\EventStore\ActionEventEmitterEventStore;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Plugin\Plugin;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EventStoreFactory
{
    public function create(
        string $eventStoreName,
        EventStore $type,
        ActionEventEmitter $actionEventEmitter,
        ContainerInterface $container,
        array $pluginServiceIds
    ): EventStore {
        $eventStore = $type;

        // Wrap in ActionEventEmitter for BC plugin support (?)
        if (count($pluginServiceIds) > 0 || $this->hasMetadataEnricherPlugin($eventStoreName, $container)) {
            $eventStore = new ActionEventEmitterEventStore($type, $actionEventEmitter);
        }

        foreach ($pluginServiceIds as $pluginServiceId) {
            /** @var Plugin $plugin */
            $plugin = $container->get($pluginServiceId);
            $plugin->attachToEventStore($eventStore);
        }

        if ($this->hasMetadataEnricherPlugin($eventStoreName, $container)) {
            $this->getMetadataEnricherPlugin($eventStoreName, $container)->attachToEventStore($eventStore);
        }

        return $eventStore;
    }

    /**
     * @param string $eventStoreName
     * @param ContainerInterface $container
     * @return Plugin
     */
    public function getMetadataEnricherPlugin(string $eventStoreName, ContainerInterface $container): Plugin
    {
        $metadataEnricherId = $this->getMetadataEnricherIdForStore($eventStoreName);

        /** @var Plugin $metadataEnricherPlugin */
        $metadataEnricherPlugin = $container->get($metadataEnricherId);

        return $metadataEnricherPlugin;
    }

    private function hasMetadataEnricherPlugin(string $eventStoreName, ContainerInterface $container)
    {
        return $container->has($this->getMetadataEnricherIdForStore($eventStoreName));
    }

    /**
     * @param string $eventStoreName
     * @return string
     */
    public function getMetadataEnricherIdForStore(string $eventStoreName): string
    {
        return sprintf('prooph_event_store.metadata_enricher_plugin.%s', $eventStoreName);
    }
}
