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
