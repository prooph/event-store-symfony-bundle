<?php
/**
 * prooph (http://getprooph.org/)
 *
 * @see       https://github.com/prooph/event-store-symfony-bundle for the canonical source repository
 * @copyright Copyright (c) 2016 prooph software GmbH (http://prooph-software.com/)
 * @license   https://github.com/prooph/event-store-symfony-bundle/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ProophTest\Bundle\EventStore\DependencyInjection\Fixture\Plugin;

use Prooph\EventStore\ActionEventEmitterEventStore;
use Prooph\EventStore\Plugin\Plugin;

class BlackHole implements Plugin
{
    public $valid = false;

    public function attachToEventStore(ActionEventEmitterEventStore $eventStore): void
    {
        $this->valid = true;
    }

    public function detachFromEventStore(ActionEventEmitterEventStore $eventStore): void
    {
        $this->valid = false;
    }
}
