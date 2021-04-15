<?php

/**
 * This file is part of prooph/event-store-symfony-bundle.
 * (c) 2014-2021 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2021 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\Bundle\EventStore\Factory;

use Prooph\EventStore\ActionEventEmitterEventStore;
use Prooph\EventStore\EventStore;

interface ActionEventEmitterFactory
{
    public static function create(EventStore $eventStore, string $actionEventEmitterFQCN): ActionEventEmitterEventStore;
}
