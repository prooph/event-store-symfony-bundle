<?php

/**
 * This file is part of prooph/event-store-symfony-bundle.
 * (c) 2014-2024 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2024 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ProophTest\Bundle\EventStore\Messenger;

use LogicException;
use Prooph\Bundle\EventStore\Messenger\EventStoreTransactionMiddleware;
use Prooph\EventStore\TransactionalEventStore;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Messenger\Test\Middleware\MiddlewareTestCase;
use Throwable;

class EventStoreTransactionMiddlewareTest extends MiddlewareTestCase
{
    /** @var TransactionalEventStore */
    private $eventStore;

    /** @var EventStoreTransactionMiddleware */
    private $middleware;

    public function setUp(): void
    {
        $this->eventStore = $this->createMock(TransactionalEventStore::class);
        $this->middleware = new EventStoreTransactionMiddleware($this->eventStore);
    }

    public function testMiddlewareWrapsInTransactionAndFlushes(): void
    {
        $this->eventStore->expects(self::once())
            ->method('beginTransaction');
        $this->eventStore->expects(self::once())
            ->method('commit');

        $this->middleware->handle(new Envelope(new stdClass()), $this->getStackMock());
    }

    public function testTransactionIsRolledBackOnException(): void
    {
        $this->expectExceptionMessage('Thrown from next middleware.');
        $this->expectException(\RuntimeException::class);
        $this->eventStore->expects(self::once())
            ->method('beginTransaction');
        $this->eventStore->expects(self::once())
            ->method('rollback');

        $this->middleware->handle(new Envelope(new stdClass()), $this->getThrowingStackMock());
    }

    public function testItResetsHandledStampsOnHandlerFailedException(): void
    {
        if (! \class_exists(HandlerFailedException::class)) {
            self::markTestSkipped('Symfony Messenger 4.2 does not support HandlerFailedException');
        }

        $this->eventStore->expects(self::once())
            ->method('beginTransaction');
        $this->eventStore->expects(self::once())
            ->method('rollback');

        $envelop = (new Envelope(new stdClass()))->with(new HandledStamp('dummy', 'dummy'));

        $exception = null;

        try {
            $this->middleware->handle($envelop, $this->getThrowingStackMock(new HandlerFailedException($envelop, [
                new LogicException('dummy exception'),
            ])));
        } catch (Throwable $e) {
            $exception = $e;
        }

        self::assertInstanceOf(HandlerFailedException::class, $exception);
        /** @var HandlerFailedException $exception */
        self::assertSame([], $exception->getEnvelope()->all(HandledStamp::class));
    }
}
