<?php

/**
 * This file is part of prooph/event-store-symfony-bundle.
 * (c) 2014-2024 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2024 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\Bundle\EventStore\Factory;

use Prooph\Bundle\EventStore\Exception\RuntimeException;
use Prooph\Common\Event\ActionEventEmitter;
use Prooph\EventStore\ActionEventEmitterEventStore;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\TransactionalActionEventEmitterEventStore;
use Prooph\EventStore\TransactionalEventStore;

class DefaultActionEventEmitterFactory implements ActionEventEmitterFactory
{
    public static function create(EventStore $eventStore, string $actionEventEmitterFQCN): ActionEventEmitterEventStore
    {
        if (! \in_array(ActionEventEmitter::class, \class_implements($actionEventEmitterFQCN), true)) {
            throw new RuntimeException(\sprintf('ActionEventEmitter "%s" must implement "%s"', $actionEventEmitterFQCN, ActionEventEmitter::class));
        }
        if ($eventStore instanceof TransactionalEventStore) {
            return static::createTransactionalEventEmitter($eventStore, $actionEventEmitterFQCN);
        }

        return static::createActionEventEmmiterEventStore($eventStore, $actionEventEmitterFQCN);
    }

    public static function createTransactionalEventEmitter(EventStore $eventStore, string $actionEventEmitterFQCN): TransactionalActionEventEmitterEventStore
    {
        if (! $eventStore instanceof TransactionalEventStore) {
            throw new RuntimeException(\sprintf('Eventstore "%s" must implement "%s"', \get_class($eventStore), TransactionalEventStore::class));
        }

        return new TransactionalActionEventEmitterEventStore($eventStore, new $actionEventEmitterFQCN(TransactionalActionEventEmitterEventStore::ALL_EVENTS));
    }

    public static function createActionEventEmmiterEventStore(EventStore $eventStore, string $actionEventEmitterFQCN): ActionEventEmitterEventStore
    {
        return new ActionEventEmitterEventStore($eventStore, new $actionEventEmitterFQCN(ActionEventEmitterEventStore::ALL_EVENTS));
    }
}
