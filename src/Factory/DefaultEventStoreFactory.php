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
