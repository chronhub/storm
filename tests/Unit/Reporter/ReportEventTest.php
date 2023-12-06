<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Reporter;

use Chronhub\Storm\Contracts\Message\MessageFactory;
use Chronhub\Storm\Contracts\Reporter\EventReporter;
use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Contracts\Tracker\MessageStory;
use Chronhub\Storm\Contracts\Tracker\MessageSubscriber;
use Chronhub\Storm\Contracts\Tracker\MessageTracker;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Reporter\DomainType;
use Chronhub\Storm\Reporter\OnDispatchPriority;
use Chronhub\Storm\Reporter\ReportEvent;
use Chronhub\Storm\Reporter\Subscribers\ConsumeEvent;
use Chronhub\Storm\Reporter\Subscribers\MakeMessage;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Tracker\TrackMessage;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(ReportEvent::class)]
final class ReportEventTest extends UnitTestCase
{
    private MessageFactory|MockObject $messageFactory;

    private ReportEvent $reporter;

    protected function setUp(): void
    {
        $this->messageFactory = $this->createMock(MessageFactory::class);
        $tracker = new TrackMessage();
        $this->reporter = new ReportEvent($tracker);

        $this->assertSame($tracker, $this->reporter->tracker());
        $this->assertEmpty($this->reporter->tracker()->listeners());
        $this->assertInstanceOf(EventReporter::class, $this->reporter);
        $this->assertEquals(DomainType::EVENT, $this->reporter->getType());
    }

    public function testRelayEvent(): void
    {
        $event = SomeEvent::fromContent(['name' => 'steph bug']);

        $this->messageFactory
            ->expects($this->once())
            ->method('__invoke')
            ->with($event)
            ->willReturn(new Message($event));

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

        $this->reporter->subscribe(...$subscribers);

        $this->reporter->relay($event);

        $this->assertTrue($messageHandled);
    }

    public function testRelayEventAsArray(): void
    {
        $eventAsArray = ['some' => 'event'];
        $event = SomeEvent::fromContent(['name' => 'steph bug']);

        $this->messageFactory
            ->expects($this->once())
            ->method('__invoke')
            ->with($eventAsArray)
            ->willReturn(new Message($event));

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

        $this->reporter->subscribe(...$subscribers);

        $this->reporter->relay($eventAsArray);

        $this->assertTrue($messageHandled);
    }

    #[DataProvider('provideConsumers')]
    public function testAlwaysMarkMessageHandlers(iterable $consumers): void
    {
        $event = SomeEvent::fromContent(['name' => 'steph bug']);

        $this->messageFactory->expects($this->once())
            ->method('__invoke')
            ->with($event)
            ->willReturn(new Message($event));

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

        $this->reporter->subscribe(...$subscribers);

        $this->reporter->relay($event);
    }

    public function testExceptionRaisedDuringDispatch(): void
    {
        $exception = new RuntimeException('some exception');

        $this->expectException($exception::class);
        $this->expectExceptionMessage('some exception');

        $event = SomeEvent::fromContent(['name' => 'steph bug']);

        $this->messageFactory
            ->expects($this->once())
            ->method('__invoke')
            ->with($event)
            ->willReturn(new Message($event));

        $consumer = function (DomainEvent $dispatchedEvent) use ($exception): never {
            $this->assertInstanceOf(SomeEvent::class, $dispatchedEvent);

            throw $exception;
        };

        $subscribers = [
            new MakeMessage($this->messageFactory),
            $this->provideRouter([$consumer]),
            new ConsumeEvent(),
        ];

        $this->reporter->subscribe(...$subscribers);

        $this->reporter->relay($event);
    }

    public static function provideConsumers(): Generator
    {
        yield [[]];

        $consumer = function (DomainEvent $dispatchedEvent): void {
            self::assertInstanceOf(SomeEvent::class, $dispatchedEvent);
        };

        yield [[$consumer]];

        yield [[$consumer, $consumer]];
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
                $this->listeners[] = $tracker->watch(
                    Reporter::DISPATCH_EVENT,
                    function (MessageStory $story): void {
                        $story->withConsumers($this->consumers);
                    }, OnDispatchPriority::ROUTE->value);
            }
        };
    }
}
