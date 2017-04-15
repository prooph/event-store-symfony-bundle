<?php

declare(strict_types=1);

namespace Prooph\Bundle\EventStore\DataCollector;

use Prooph\Common\Event\ActionEvent;
use Prooph\EventStore\ActionEventEmitterEventStore;
use Prooph\EventStore\Pdo\PdoStreamIterator;
use Prooph\EventStore\TransactionalActionEventEmitterEventStore;
use Symfony\Component\Stopwatch\StopwatchEvent;

class DebugEvent
{
    /**
     * @var ActionEvent
     */
    private $event;
    /**
     * @var array
     */
    private $metaData;
    /**
     * @var StopwatchEvent
     */
    private $stopwatchEvent;

    public function __construct(ActionEvent $event, StopwatchEvent $stopwatchEvent, array $metaData = [])
    {
        $this->event = $event;
        $this->metaData = $metaData;
        $this->stopwatchEvent = $stopwatchEvent;
    }

    /**
     * @return ActionEvent
     */
    public function getEvent(): ActionEvent
    {
        return $this->event;
    }

    /**
     * @return array
     */
    public function getMetaData(): array
    {
        return $this->metaData;
    }

    /**
     * @return StopwatchEvent
     */
    public function getStopwatchEvent(): StopwatchEvent
    {
        return $this->stopwatchEvent;
    }

    public function getStreamName()
    {
        if (\in_array($this->event->getName(), [
                TransactionalActionEventEmitterEventStore::EVENT_COMMIT,
                TransactionalActionEventEmitterEventStore::EVENT_BEGIN_TRANSACTION,
                TransactionalActionEventEmitterEventStore::EVENT_ROLLBACK,
            ]
        )) {
            return ;
        }

        switch ($this->event->getName()) {
            case ActionEventEmitterEventStore::EVENT_LOAD:
            case ActionEventEmitterEventStore::EVENT_APPEND_TO:
            case ActionEventEmitterEventStore::EVENT_DELETE:
                return (string)$this->event->getParam('streamName');

            case ActionEventEmitterEventStore::EVENT_CREATE:
                return (string)$this->event->getParam('stream')->streamName();

            default:
                throw new \Exception('Unknown EventType');
        }
    }

    public function getStreamEvents()
    {
        if (\in_array($this->event->getName(), [
                TransactionalActionEventEmitterEventStore::EVENT_COMMIT,
                TransactionalActionEventEmitterEventStore::EVENT_BEGIN_TRANSACTION,
                TransactionalActionEventEmitterEventStore::EVENT_ROLLBACK,
                TransactionalActionEventEmitterEventStore::EVENT_DELETE,
            ]
        )) {
            return [];
        }

        switch ($this->event->getName()) {
            case ActionEventEmitterEventStore::EVENT_LOAD:
            case ActionEventEmitterEventStore::EVENT_APPEND_TO:

                /** @var PdoStreamIterator $streamEvents */
                if (!$this->event->getParam('streamNotFound')) {
                    $streamEvents = iterator_to_array($this->event->getParam('streamEvents'));
                } else {
                    $streamEvents[] = 'no aggregate root with events found';
                }

                return $streamEvents;

            case ActionEventEmitterEventStore::EVENT_CREATE:
                $stream = $this->event->getParam('stream');
                $streamEvents = iterator_to_array($stream->streamEvents());

                return $streamEvents;

            default:
                throw new \Exception('Unknown StreamEvents');
        }
    }
}
