<?php
/**
 * prooph (http://getprooph.org/)
 *
 * @see       https://github.com/prooph/event-store-symfony-bundle for the canonical source repository
 * @copyright Copyright (c) 2016 prooph software GmbH (http://prooph-software.com/)
 * @license   https://github.com/prooph/event-store-symfony-bundle/blob/master/LICENSE.md New BSD License
 */

declare (strict_types = 1);

namespace ProophTest\Bundle\EventStore\DependencyInjection\Fixture\Adapter;

use DateTimeInterface;
use Iterator;
use Prooph\EventStore\Adapter\Adapter;
use Prooph\EventStore\Stream\Stream;
use Prooph\EventStore\Stream\StreamName;

class BlackHole implements Adapter
{
    public function create(Stream $stream)
    {
    }

    public function appendTo(StreamName $streamName, Iterator $domainEvents)
    {
    }

    public function load(StreamName $streamName, $minVersion = null)
    {
    }

    public function loadEvents(StreamName $streamName, array $metadata = [], $minVersion = null)
    {
    }

    public function replay(StreamName $streamName, DateTimeInterface $since = null, array $metadata = [])
    {
    }
}
