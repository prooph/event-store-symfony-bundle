<?php
declare(strict_types=1);

namespace ProophTest\Bundle\EventStore\Command\Fixture\Event;

use Prooph\Common\Messaging\DomainEvent;
use Prooph\Common\Messaging\PayloadTrait;

class SomethingWasDone extends DomainEvent
{
    use PayloadTrait;
}
