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
use Prooph\EventStore\Adapter\Adapter;
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
        $eventStore = new ActionEventEmitterEventStore($type, $actionEventEmitter);

        foreach ($pluginServiceIds as $pluginServiceId) {
            /** @var Plugin $plugin */
            $plugin = $container->get($pluginServiceId);
            $plugin->attachToEventStore($eventStore);
        }

        $metadataEnricherId = sprintf('prooph_event_store.%s.%s', 'metadata_enricher_plugin', $eventStoreName);

        /** @var Plugin $metadataEnricherPlugin */
        $metadataEnricherPlugin = $container->get($metadataEnricherId);

        $metadataEnricherPlugin->attachToEventStore($eventStore);

        return $eventStore;
    }
}
