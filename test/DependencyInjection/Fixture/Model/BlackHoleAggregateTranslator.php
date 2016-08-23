<?php
/**
 * prooph (http://getprooph.org/)
 *
 * @see       https://github.com/prooph/event-store-symfony-bundle for the canonical source repository
 * @copyright Copyright (c) 2016 prooph software GmbH (http://prooph-software.com/)
 * @license   https://github.com/prooph/event-store-symfony-bundle/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ProophTest\Bundle\EventStore\DependencyInjection\Fixture\Model;

use Iterator;
use Prooph\Common\Messaging\Message;
use Prooph\EventStore\Aggregate\AggregateType;

class BlackHoleAggregateTranslator implements \Prooph\EventStore\Aggregate\AggregateTranslator
{

    /**
     * @param object $eventSourcedAggregateRoot
     * @return int
     */
    public function extractAggregateVersion($eventSourcedAggregateRoot)
    {
        // TODO: Implement extractAggregateVersion() method.
    }

    /**
     * @param object $eventSourcedAggregateRoot
     * @return string
     */
    public function extractAggregateId($eventSourcedAggregateRoot)
    {
        // TODO: Implement extractAggregateId() method.
    }

    /**
     * @param AggregateType $aggregateType
     * @param Iterator $historyEvents
     * @return object reconstructed EventSourcedAggregateRoot
     */
    public function reconstituteAggregateFromHistory(AggregateType $aggregateType, Iterator $historyEvents)
    {
        // TODO: Implement reconstituteAggregateFromHistory() method.
    }

    /**
     * @param object $eventSourcedAggregateRoot
     * @return Message[]
     */
    public function extractPendingStreamEvents($eventSourcedAggregateRoot)
    {
        // TODO: Implement extractPendingStreamEvents() method.
    }

    /**
     * @param $anEventSourcedAggregateRoot
     * @param Iterator $events
     */
    public function replayStreamEvents($anEventSourcedAggregateRoot, Iterator $events)
    {
        // TODO: Implement replayStreamEvents() method.
    }
}
