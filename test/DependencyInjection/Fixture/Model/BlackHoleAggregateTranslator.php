<?php
/**
 * prooph (http://getprooph.org/)
 *
 * @see       https://github.com/prooph/event-store-symfony-bundle for the canonical source repository
 * @copyright Copyright (c) 2016 - 2019 Alexander Miertsch <kontakt@codeliner.ws>
 * @license   https://github.com/prooph/event-store-symfony-bundle/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ProophTest\Bundle\EventStore\DependencyInjection\Fixture\Model;

use Iterator;
use Prooph\Common\Messaging\Message;
use Prooph\EventSourcing\Aggregate\AggregateTranslator;
use Prooph\EventSourcing\Aggregate\AggregateType;

class BlackHoleAggregateTranslator implements AggregateTranslator
{
    /**
     * @param object $eventSourcedAggregateRoot
     */
    public function extractAggregateVersion($eventSourcedAggregateRoot): int
    {
        // TODO: Implement extractAggregateVersion() method.
    }

    /**
     * @param object $eventSourcedAggregateRoot
     */
    public function extractAggregateId($eventSourcedAggregateRoot): string
    {
        // TODO: Implement extractAggregateId() method.
    }

    /**
     * @return object reconstructed EventSourcedAggregateRoot
     */
    public function reconstituteAggregateFromHistory(AggregateType $aggregateType, Iterator $historyEvents)
    {
        // TODO: Implement reconstituteAggregateFromHistory() method.
    }

    /**
     * @param object $eventSourcedAggregateRoot
     *
     * @return Message[]
     */
    public function extractPendingStreamEvents($eventSourcedAggregateRoot): array
    {
        // TODO: Implement extractPendingStreamEvents() method.
    }

    /**
     * @param object $eventSourcedAggregateRoot
     * @param Iterator $events
     */
    public function replayStreamEvents($eventSourcedAggregateRoot, Iterator $events): void
    {
        // TODO: Implement replayStreamEvents() method.
    }
}
