<?php

/**
 * This file is part of prooph/event-store-symfony-bundle.
 * (c) 2014-2022 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2022 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\Bundle\EventStore\Messenger;

use Prooph\EventStore\TransactionalEventStore;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Throwable;

final class EventStoreTransactionMiddleware implements MiddlewareInterface
{
    private TransactionalEventStore $eventStore;

    public function __construct(TransactionalEventStore $eventStore)
    {
        $this->eventStore = $eventStore;
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $this->eventStore->beginTransaction();

        try {
            $envelope = $stack->next()->handle($envelope, $stack);

            $this->eventStore->commit();
        } catch (Throwable $e) {
            $this->eventStore->rollback();

            if ($e instanceof HandlerFailedException) {
                // Remove all HandledStamp from the envelope so the retry will execute all handlers again.
                // When a handler fails, the queries of allegedly successful previous handlers just got rolled back.
                throw new HandlerFailedException(
                    $e->getEnvelope()->withoutAll(HandledStamp::class),
                    $e->getNestedExceptions()
                );
            }

            throw $e;
        }

        return $envelope;
    }
}
