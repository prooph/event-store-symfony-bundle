<?php

declare(strict_types=1);

namespace Prooph\Bundle\EventStore\DataCollector;

use Prooph\Common\Event\ActionEvent;
use Prooph\EventStore\ActionEventEmitterEventStore;
use Prooph\EventStore\Plugin\Plugin;
use Prooph\EventStore\TransactionalActionEventEmitterEventStore;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\Stopwatch\Stopwatch;

class DebugPlugin extends DataCollector implements Plugin
{
    /**
     * @var DebugEvent[]
     */
    private $eventStoreActions;
    /**
     * @var Stopwatch
     */
    private $stopwatch;

    public function __construct(Stopwatch $stopwatch, array $config = [])
    {
        $this->stopwatch = $stopwatch;
        $this->config = $config;
    }

    /**
     * @param ActionEventEmitterEventStore|TransactionalActionEventEmitterEventStore $eventStore
     */
    public function attachToEventStore(ActionEventEmitterEventStore $eventStore): void
    {

        // Track transactions
        if ($eventStore instanceof TransactionalActionEventEmitterEventStore) {

            $eventStore->attach(TransactionalActionEventEmitterEventStore::EVENT_BEGIN_TRANSACTION, function (ActionEvent $event) {
                $transactionInstance = 1;
//                static $transactionInstance = 0;
//                $transactionInstance++;

                $time = $this->stopwatch->start('event_store.transaction_'.$transactionInstance, 'section');
                $this->eventStoreActions[] = new DebugEvent($event, $time);
            }, 1000);

            foreach ([TransactionalActionEventEmitterEventStore::EVENT_COMMIT, TransactionalActionEventEmitterEventStore::EVENT_ROLLBACK] as $eventStoreEvent) {
                $eventStore->attach($eventStoreEvent, function (ActionEvent $event) {
                    //                    static $transactionInstance = 0;
//                    $transactionInstance++;
                    $transactionInstance = 1;
                    $time = $this->stopwatch->stop('event_store.transaction_'.$transactionInstance);
                    $this->eventStoreActions[] = new DebugEvent($event, $time);
                }, -1000);
            }
        }

        $trackedActionEvents = [
            ActionEventEmitterEventStore::EVENT_CREATE,
            ActionEventEmitterEventStore::EVENT_LOAD,
            ActionEventEmitterEventStore::EVENT_APPEND_TO,
            ActionEventEmitterEventStore::EVENT_DELETE,
        ];
        foreach ($trackedActionEvents as $eventStoreEvent) {
            $eventStore->attach($eventStoreEvent, function (ActionEvent $event) use ($eventStoreEvent, $eventStore) {
                if ($event->getName() === $eventStore::EVENT_CREATE) {
                    $stopName = sprintf('%s:%s', $event->getName(), $event->getParam('stream')->streamName());
                } else {
                    $stopName = sprintf('%s:%s', $event->getName(), $event->getParam('streamName'));
                }

                $this->stopwatch->start($stopName);

            }, 1000);

            $eventStore->attach($eventStoreEvent, function (ActionEvent $event) use ($eventStoreEvent) {
                if ($event->getName() === 'create') {
                    $stopName = sprintf('%s:%s', $event->getName(), $event->getParam('stream')->streamName());
                } else {
                    $stopName = sprintf('%s:%s', $event->getName(), $event->getParam('streamName'));
                }
                $time = $this->stopwatch->stop($stopName);

                $debugEvent = new DebugEvent($event, $time);

                $this->eventStoreActions[] = $debugEvent;
            }, -1000);
        }
    }

    public function detachFromEventStore(ActionEventEmitterEventStore $eventStore): void
    {
    }

    public function config(string $eventStore): array
    {
        return $this->data['config'][$eventStore] ?? [];
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'event_store';
    }

    /**
     * @inheritdoc
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data = [];
        $allStreams = [];
        foreach ($this->eventStoreActions as $debugEvent) {
            // Don't count non-returned streams

            $debugEvent->getStreamName() === null ?: $allStreams[] = $debugEvent->getStreamName();

            /** @var DebugEvent $debugEvent */
            $actionEvent = $debugEvent->getEvent();
            $entry['event_name'] = $actionEvent->getName();

            if($actionEvent->getName() === TransactionalActionEventEmitterEventStore::EVENT_BEGIN_TRANSACTION) {
                $entry['duration'] = 0;
            } else {
                $entry['duration'] = $debugEvent->getStopwatchEvent()->getDuration();
            }

            $entry['stream_events'] = $debugEvent->getStreamEvents();
            $entry['stream_name'] = $debugEvent->getStreamName();
            $this->data['event_store_actions'][] = $entry;

            $this->data['metrics']['total_events']++;
            $this->data['metrics']['total_messages'] += count($entry['stream_events']);
            $this->data['metrics']['total_duration'] += $entry['duration'];
        }
        $this->data['metrics']['total_streams'] = count(\array_unique($allStreams));

        $this->data = $this->cloneVar($this->data);
    }

    /**
     * @return array
     */
    public function getEventStoreActions()
    {
        return $this->data['event_store_actions'];
    }

    public function getMetrics()
    {
        return $this->data['metrics'];
    }
}
