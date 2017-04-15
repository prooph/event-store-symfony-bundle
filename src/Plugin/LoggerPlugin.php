<?php

declare(strict_types=1);

namespace Prooph\Bundle\EventStore\Plugin;

use Prooph\EventStore\ActionEventEmitterEventStore;
use Prooph\EventStore\Plugin\Plugin;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Prooph\Common\Event\ActionEvent;

class LoggerPlugin implements Plugin
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    public function attachToEventStore(ActionEventEmitterEventStore $eventStore): void
    {
        foreach ($eventStore::ALL_EVENTS as $eventStoreEvent) {
            $eventStore->attach($eventStoreEvent, function (ActionEvent $event) use ($eventStoreEvent, $eventStore) {
                $context = [];
                if($event->getParam('streamName', null) !== null) {
                    $context['streamName'] = (string) $event->getParam('streamName');
                }
                $this->logger->debug(sprintf('Action %s', $event->getName()), $context);
            }, 1000);
        }
    }

    public function detachFromEventStore(ActionEventEmitterEventStore $eventStore): void
    {
    }

}
