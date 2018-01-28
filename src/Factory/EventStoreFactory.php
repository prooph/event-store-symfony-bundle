<?php

declare(strict_types=1);

namespace Prooph\Bundle\EventStore\Factory;

use Prooph\EventStore\EventStore;
use Psr\Container\ContainerInterface;

interface EventStoreFactory
{
    public function createEventStore(
        string $eventStoreName,
        EventStore $eventStore,
        ActionEventEmitterFactory $actionEventEmitterFactory,
        string $actionEventEmitter,
        bool $wrapActionEventEmitter,
        ContainerInterface $container,
        array $plugins = []
    ): EventStore;
}
