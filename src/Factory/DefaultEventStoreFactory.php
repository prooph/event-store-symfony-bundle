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

            if (! $plugin instanceof Plugin) {
                throw new RuntimeException(\sprintf(
                    'Plugin %s does not implement the Plugin interface',
                    $pluginAlias
                ));
            }

            $plugin->attachToEventStore($actionEventEmittingEventStore);
        }

        return $actionEventEmittingEventStore;
    }
}
