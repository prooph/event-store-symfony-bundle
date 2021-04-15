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

namespace ProophTest\Bundle\EventStore\DependencyInjection\Fixture\Plugin;

use Prooph\EventStore\ActionEventEmitterEventStore;
use Prooph\EventStore\Plugin\Plugin;

class BlackHole implements Plugin
{
    public $stores = [];
    public $valid = false;

    public function attachToEventStore(ActionEventEmitterEventStore $eventStore): void
    {
        $this->stores[] = $eventStore;
        $this->valid = true;
    }

    public function detachFromEventStore(ActionEventEmitterEventStore $eventStore): void
    {
        $this->stores = \array_filter($this->stores, function ($store) use ($eventStore) {
            return $store !== $eventStore;
        });
        $this->valid = false;
    }
}
