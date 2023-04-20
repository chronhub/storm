<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Reporter;

use Chronhub\Storm\Contracts\Message\MessageFactory;
use Chronhub\Storm\Contracts\Reporter\QueryReporter;
use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Contracts\Tracker\MessageStory;
use Chronhub\Storm\Contracts\Tracker\MessageSubscriber;
use Chronhub\Storm\Contracts\Tracker\MessageTracker;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Reporter\DomainQuery;
use Chronhub\Storm\Reporter\DomainType;
use Chronhub\Storm\Reporter\Exceptions\MessageNotHandled;
use Chronhub\Storm\Reporter\OnDispatchPriority;
use Chronhub\Storm\Reporter\ReportQuery;
use Chronhub\Storm\Reporter\Subscribers\ConsumeEvent;
use Chronhub\Storm\Reporter\Subscribers\ConsumeQuery;
use Chronhub\Storm\Reporter\Subscribers\MakeMessage;
use Chronhub\Storm\Tests\Stubs\Double\SomeQuery;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Tracker\TrackMessage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use React\Promise\Deferred;
use RuntimeException;

#[CoversClass(ReportQuery::class)]
#[CoversClass(MessageNotHandled::class)]
final class ReportQueryTest extends UnitTestCase
{
    private MessageFactory|MockObject $messageFactory;

    private ReportQuery $reporter;

    private PromiseHandlerStub $promiseHandler;

    protected function setUp(): void
    {
        $this->messageFactory = $this->createMock(MessageFactory::class);
        $tracker = new TrackMessage();
        $this->reporter = new ReportQuery($tracker);
        $this->promiseHandler = new PromiseHandlerStub();

        $this->assertSame($tracker, $this->reporter->tracker());
        $this->assertEmpty($this->reporter->tracker()->listeners());
        $this->assertInstanceOf(QueryReporter::class, $this->reporter);
        $this->assertEquals(DomainType::QUERY, $this->reporter->getType());
    }

    public function testRelayQuery(): void
    {
        $query = SomeQuery::fromContent(['name' => 'steph bug']);
        $this->messageFactory
            ->expects($this->once())
            ->method('__invoke')
            ->with($query)
            ->willReturn(new Message($query));

        $messageHandled = false;
        $consumer = function (DomainQuery $dispatchedQuery, Deferred $promise) use (&$messageHandled): void {
            $this->assertInstanceOf(SomeQuery::class, $dispatchedQuery);

            $promise->resolve($dispatchedQuery->toContent()['name']);

            $messageHandled = true;
        };

        $subscribers = [
            new MakeMessage($this->messageFactory),
            $this->provideRouter([$consumer]),
            new ConsumeQuery(),
        ];

        $this->reporter->subscribe(...$subscribers);

        $promise = $this->reporter->relay($query);

        $this->assertTrue($messageHandled);

        $this->assertEquals('steph bug', $this->promiseHandler->handlePromise($promise, true));
    }

    public function testRelayQueryAsArray(): void
    {
        $queryAsArray = ['some' => 'query'];
        $query = SomeQuery::fromContent(['name' => 'steph bug']);

        $this->messageFactory
            ->expects($this->once())
            ->method('__invoke')
            ->with($queryAsArray)
            ->willReturn(new Message($query));

        $messageHandled = false;
        $consumer = function (DomainQuery $dispatchedQuery, Deferred $promise) use (&$messageHandled): void {
            $this->assertInstanceOf(SomeQuery::class, $dispatchedQuery);

            $promise->resolve($dispatchedQuery->toContent()['name']);

            $messageHandled = true;
        };

        $subscribers = [
            new MakeMessage($this->messageFactory),
            $this->provideRouter([$consumer]),
            new ConsumeQuery(),
        ];

        $this->reporter->subscribe(...$subscribers);
        $promise = $this->reporter->relay($queryAsArray);

        $this->assertTrue($messageHandled);
        $this->assertEquals('steph bug', $this->promiseHandler->handlePromise($promise, true));
    }

    public function testExceptionRaisedWhenQueryNotHandled(): void
    {
        $this->expectException(MessageNotHandled::class);

        $event = SomeQuery::fromContent(['name' => 'steph bug']);

        $this->messageFactory
            ->expects($this->once())
            ->method('__invoke')
            ->with($event)
            ->willReturn(new Message($event));

        $assertMessageIsNotAcked = new class() implements MessageSubscriber
        {
            private array $listeners = [];

            public function detachFromReporter(MessageTracker $tracker): void
            {
                foreach ($this->listeners as $listener) {
                    $tracker->forget($listener);
                }
            }

            public function attachToReporter(MessageTracker $tracker): void
            {
                $this->listeners[] = $tracker->watch(Reporter::FINALIZE_EVENT, function (MessageStory $story): void {
                    TestCase::assertFalse($story->isHandled());
                }, -10000);
            }
        };

        $subscribers = [
            new MakeMessage($this->messageFactory),
            new ConsumeQuery(),
            $this->provideRouter([]),
            $assertMessageIsNotAcked,
        ];

        $this->reporter->subscribe(...$subscribers);
        $this->reporter->relay($event);
    }

    public function testExceptionRaisedDuringDispatch(): void
    {
        $exception = new RuntimeException('some exception');

        $this->expectException($exception::class);
        $this->expectExceptionMessage('some exception');

        $query = SomeQuery::fromContent(['name' => 'steph bug']);

        $this->messageFactory
            ->expects($this->once())
            ->method('__invoke')
            ->with($query)
            ->willReturn(new Message($query));

        $consumer = function (DomainQuery $dispatchedQuery) use ($exception): never {
            $this->assertInstanceOf(SomeQuery::class, $dispatchedQuery);

            throw $exception;
        };

        $subscribers = [
            new MakeMessage($this->messageFactory),
            $this->provideRouter([$consumer]),
            new ConsumeEvent(),
        ];

        $this->reporter->subscribe(...$subscribers);
        $this->reporter->relay($query);
    }

    private function provideRouter(iterable $consumers): MessageSubscriber
    {
        return new class($consumers) implements MessageSubscriber
        {
            private array $listeners = [];

            public function __construct(private readonly iterable $consumers)
            {
            }

            public function detachFromReporter(MessageTracker $tracker): void
            {
                foreach ($this->listeners as $listener) {
                    $tracker->forget($listener);
                }
            }

            public function attachToReporter(MessageTracker $tracker): void
            {
                $this->listeners[] = $tracker->watch(Reporter::DISPATCH_EVENT, function (MessageStory $story): void {
                    $story->withConsumers($this->consumers);
                }, OnDispatchPriority::ROUTE->value);
            }
        };
    }
}
