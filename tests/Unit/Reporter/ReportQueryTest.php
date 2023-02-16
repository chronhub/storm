<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Reporter;

use Generator;
use Throwable;
use RuntimeException;
use React\Promise\Deferred;
use PHPUnit\Framework\TestCase;
use Chronhub\Storm\Message\Message;
use React\Promise\PromiseInterface;
use Prophecy\Prophecy\ObjectProphecy;
use Chronhub\Storm\Reporter\DomainQuery;
use Chronhub\Storm\Reporter\ReportQuery;
use Chronhub\Storm\Tracker\TrackMessage;
use Chronhub\Storm\Tests\Double\SomeQuery;
use Chronhub\Storm\Tests\ProphecyTestCase;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Reporter\OnDispatchPriority;
use Chronhub\Storm\Contracts\Tracker\MessageStory;
use Chronhub\Storm\Contracts\Message\MessageFactory;
use Chronhub\Storm\Contracts\Tracker\MessageTracker;
use Chronhub\Storm\Reporter\Subscribers\MakeMessage;
use Chronhub\Storm\Reporter\Subscribers\ConsumeEvent;
use Chronhub\Storm\Reporter\Subscribers\ConsumeQuery;
use Chronhub\Storm\Contracts\Tracker\MessageSubscriber;
use Chronhub\Storm\Reporter\Exceptions\MessageNotHandled;

final class ReportQueryTest extends ProphecyTestCase
{
    private MessageFactory|ObjectProphecy $messageFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->messageFactory = $this->prophesize(MessageFactory::class);
    }

    /**
     * @test
     */
    public function it_relay_query(): void
    {
        $query = SomeQuery::fromContent(['name' => 'steph bug']);
        $this->messageFactory->__invoke($query)->willReturn(new Message($query))->shouldBeCalledOnce();

        $tracker = new TrackMessage();

        $reporter = new ReportQuery($tracker);
        $this->assertSame($tracker, $reporter->tracker());

        $messageHandled = false;

        $consumer = function (DomainQuery $dispatchedQuery, Deferred $promise) use (&$messageHandled): void {
            $this->assertInstanceOf(SomeQuery::class, $dispatchedQuery);

            $promise->resolve($dispatchedQuery->toContent()['name']);

            $messageHandled = true;
        };

        $subscribers = [
            new MakeMessage($this->messageFactory->reveal()),
            $this->provideRouter([$consumer]),
            new ConsumeQuery(),
        ];

        $reporter->subscribe(...$subscribers);

        $promise = $reporter->relay($query);

        $this->assertTrue($messageHandled);

        $this->assertEquals('steph bug', $this->handlePromise($promise));
    }

    /**
     * @test
     */
    public function it_relay_query_as_array(): void
    {
        $queryAsArray = ['some' => 'query'];
        $query = SomeQuery::fromContent(['name' => 'steph bug']);

        $this->messageFactory->__invoke($queryAsArray)->willReturn(new Message($query))->shouldBeCalledOnce();

        $tracker = new TrackMessage();

        $reporter = new ReportQuery($tracker);
        $this->assertSame($tracker, $reporter->tracker());

        $messageHandled = false;

        $consumer = function (DomainQuery $dispatchedQuery, Deferred $promise) use (&$messageHandled): void {
            $this->assertInstanceOf(SomeQuery::class, $dispatchedQuery);

            $promise->resolve($dispatchedQuery->toContent()['name']);

            $messageHandled = true;
        };

        $subscribers = [
            new MakeMessage($this->messageFactory->reveal()),
            $this->provideRouter([$consumer]),
            new ConsumeQuery(),
        ];

        $reporter->subscribe(...$subscribers);

        $promise = $reporter->relay($queryAsArray);

        $this->assertTrue($messageHandled);

        $this->assertEquals('steph bug', $this->handlePromise($promise));
    }

    /**
     * @test
     */
    public function it_raise_exception_when_message_not_handled(): void
    {
        $this->expectException(MessageNotHandled::class);

        $event = SomeQuery::fromContent(['name' => 'steph bug']);
        $this->messageFactory->__invoke($event)->willReturn(new Message($event))->shouldBeCalledOnce();

        $tracker = new TrackMessage();
        $reporter = new ReportQuery($tracker);

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
            new MakeMessage($this->messageFactory->reveal()),
            new ConsumeQuery(),
            $this->provideRouter([]),
            $assertMessageIsNotAcked,
        ];

        $reporter->subscribe(...$subscribers);

        $reporter->relay($event);
    }

    /**
     * @test
     */
    public function it_raise_exception_caught_during_dispatch_of_query(): void
    {
        $exception = new RuntimeException('some exception');

        $this->expectException($exception::class);
        $this->expectExceptionMessage('some exception');

        $query = SomeQuery::fromContent(['name' => 'steph bug']);
        $this->messageFactory->__invoke($query)->willReturn(new Message($query))->shouldBeCalledOnce();

        $tracker = new TrackMessage();
        $reporter = new ReportQuery($tracker);

        $consumer = function (DomainQuery $dispatchedQuery) use ($exception): never {
            $this->assertInstanceOf(SomeQuery::class, $dispatchedQuery);

            throw $exception;
        };

        $subscribers = [
            new MakeMessage($this->messageFactory->reveal()),
            $this->provideRouter([$consumer]),
            new ConsumeEvent(),
        ];

        $reporter->subscribe(...$subscribers);

        $reporter->relay($query);
    }

    public function provideEvent(): Generator
    {
        yield [SomeQuery::fromContent(['name' => 'steph bug']), SomeQuery::class];

        yield [SomeQuery::fromContent(['name' => 'steph bug'])
            ->withHeader(Header::EVENT_TYPE, 'some.query'), 'some.query',
        ];
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

    private function handlePromise(PromiseInterface $promise): mixed
    {
        $exception = null;
        $result = null;

        $promise->then(
            static function ($data) use (&$result): void {
                $result = $data;
            },
            static function ($exc) use (&$exception): void {
                $exception = $exc;
            }
        );

        if ($exception instanceof Throwable) {
            return $exception;
        }

        return $result;
    }
}
