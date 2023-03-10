<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Reporter;

use Generator;
use RuntimeException;
use PHPUnit\Framework\TestCase;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Reporter\ReportEvent;
use Chronhub\Storm\Tracker\TrackMessage;
use Chronhub\Storm\Tests\Double\SomeEvent;
use PHPUnit\Framework\MockObject\Exception;
use Chronhub\Storm\Contracts\Message\Header;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Reporter\OnDispatchPriority;
use Chronhub\Storm\Contracts\Tracker\MessageStory;
use Chronhub\Storm\Contracts\Message\MessageFactory;
use Chronhub\Storm\Contracts\Tracker\MessageTracker;
use Chronhub\Storm\Reporter\Subscribers\MakeMessage;
use Chronhub\Storm\Reporter\Subscribers\ConsumeEvent;
use Chronhub\Storm\Contracts\Tracker\MessageSubscriber;

#[CoversClass(ReportEvent::class)]
final class ReportEventTest extends UnitTestCase
{
    private MessageFactory|MockObject $messageFactory;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->messageFactory = $this->createMock(MessageFactory::class);
    }

    #[Test]
    public function it_relay_event(): void
    {
        $event = SomeEvent::fromContent(['name' => 'steph bug']);

        $this->messageFactory->expects($this->once())
            ->method('__invoke')
            ->with($event)
            ->willReturn(new Message($event));

        $tracker = new TrackMessage();

        $reporter = new ReportEvent($tracker);
        $this->assertSame($tracker, $reporter->tracker());

        $messageHandled = false;

        $consumer = function (DomainEvent $dispatchedEvent) use (&$messageHandled): void {
            $this->assertInstanceOf(SomeEvent::class, $dispatchedEvent);

            $messageHandled = true;
        };

        $subscribers = [
            new MakeMessage($this->messageFactory),
            $this->provideRouter([$consumer]),
            new ConsumeEvent(),
        ];

        $reporter->subscribe(...$subscribers);

        $reporter->relay($event);

        $this->assertTrue($messageHandled);
    }

    #[Test]
    public function it_relay_command_as_array(): void
    {
        $eventAsArray = ['some' => 'event'];
        $event = SomeEvent::fromContent(['name' => 'steph bug']);

        $this->messageFactory->expects($this->once())
            ->method('__invoke')
            ->with($eventAsArray)
            ->willReturn(new Message($event));

        $tracker = new TrackMessage();

        $reporter = new ReportEvent($tracker);
        $this->assertSame($tracker, $reporter->tracker());

        $messageHandled = false;

        $consumer = function (DomainEvent $dispatchedEvent) use (&$messageHandled): void {
            $this->assertInstanceOf(SomeEvent::class, $dispatchedEvent);

            $messageHandled = true;
        };

        $subscribers = [
            new MakeMessage($this->messageFactory),
            $this->provideRouter([$consumer]),
            new ConsumeEvent(),
        ];

        $reporter->subscribe(...$subscribers);

        $reporter->relay($eventAsArray);

        $this->assertTrue($messageHandled);
    }

    #[DataProvider('provideConsumers')]
    #[Test]
    public function it_always_considered_domain_event_acked_regardless_of_consumers(iterable $consumers): void
    {
        $event = SomeEvent::fromContent(['name' => 'steph bug']);

        $this->messageFactory->expects($this->once())
            ->method('__invoke')
            ->with($event)
            ->willReturn(new Message($event));

        $tracker = new TrackMessage();
        $reporter = new ReportEvent($tracker);

        $assertMessageIsAcked = new class() implements MessageSubscriber
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
                    TestCase::assertTrue($story->isHandled());
                }, -10000);
            }
        };

        $subscribers = [
            new MakeMessage($this->messageFactory),
            new ConsumeEvent(),
            $this->provideRouter($consumers),
            $assertMessageIsAcked,
        ];

        $reporter->subscribe(...$subscribers);

        $reporter->relay($event);
    }

    #[Test]
    public function it_raise_exception_caught_during_dispatch_of_event(): void
    {
        $exception = new RuntimeException('some exception');

        $this->expectException($exception::class);
        $this->expectExceptionMessage('some exception');

        $event = SomeEvent::fromContent(['name' => 'steph bug']);

        $this->messageFactory->expects($this->once())
            ->method('__invoke')
            ->with($event)
            ->willReturn(new Message($event));

        $tracker = new TrackMessage();
        $reporter = new ReportEvent($tracker);

        $consumer = function (DomainEvent $dispatchedEvent) use ($exception): never {
            $this->assertInstanceOf(SomeEvent::class, $dispatchedEvent);

            throw $exception;
        };

        $subscribers = [
            new MakeMessage($this->messageFactory),
            $this->provideRouter([$consumer]),
            new ConsumeEvent(),
        ];

        $reporter->subscribe(...$subscribers);

        $reporter->relay($event);
    }

    public function provideEvent(): Generator
    {
        yield [SomeEvent::fromContent(['name' => 'steph bug']), SomeEvent::class];

        yield [SomeEvent::fromContent(['name' => 'steph bug'])
            ->withHeader(Header::EVENT_TYPE, 'some.event'), 'some.event',
        ];
    }

    public static function provideConsumers(): Generator
    {
        yield [[]];

        $consumer = function (DomainEvent $dispatchedEvent): void {
            self::assertInstanceOf(SomeEvent::class, $dispatchedEvent);
        };

        yield[[$consumer]];

        yield[[$consumer, $consumer]];
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
