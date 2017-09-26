<?php

declare(strict_types=1);

namespace Prooph\Bundle\EventStore\Factory;

use Prooph\EventStore\ActionEventEmitterEventStore;
use Prooph\EventStore\EventStore;

interface ActionEventEmitterFactory
{
    public static function create(EventStore $eventStore, string $actionEventEmitterFQCN): ActionEventEmitterEventStore;
}
