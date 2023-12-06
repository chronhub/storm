<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Reporter;

use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Reporter\OnDispatchPriority;
use Chronhub\Storm\Reporter\Subscribers\ConsumeEvent;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Tracker\TrackMessage;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use Throwable;

#[CoversClass(ConsumeEvent::class)]
final class ConsumeEventTest extends UnitTestCase
{
    public function testSubscriber(): void
    {
        $subscriber = new ConsumeEvent();

        AssertMessageListener::isTrackedAndCanBeUntracked(
            $subscriber,
            Reporter::DISPATCH_EVENT,
            OnDispatchPriority::INVOKE_HANDLER->value
        );
    }

    public function testConsumeEvent(): void
    {
        $tracker = new TrackMessage();
        $subscriber = new ConsumeEvent();
        $subscriber->attachToReporter($tracker);

        $event = SomeEvent::fromContent([]);

        $count = 0;

        $consumers = [
            function (DomainEvent $event) use (&$count): void {
                $this->assertInstanceOf(SomeEvent::class, $event);

                $count++;
            },
            function (DomainEvent $event) use (&$count): void {
                $this->assertInstanceOf(SomeEvent::class, $event);

                $count++;
            },
            function (DomainEvent $event) use (&$count): void {
                $this->assertInstanceOf(SomeEvent::class, $event);

                $count++;
            },
        ];

        $story = $tracker->newStory(Reporter::DISPATCH_EVENT);

        $this->assertNull($story->consumers()->current());
        $this->assertFalse($story->isHandled());

        $story->withMessage(new Message($event));
        $story->withConsumers($consumers);

        $tracker->disclose($story);

        $this->assertSame(3, $count);
        $this->assertTrue($story->isHandled());
    }

    public function testConsumeTillNoExceptionRaised(): void
    {
        $tracker = new TrackMessage();
        $subscriber = new ConsumeEvent();
        $subscriber->attachToReporter($tracker);

        $event = SomeEvent::fromContent([]);

        $count = 0;
        $consumers = [
            function (DomainEvent $event) use (&$count): void {
                $this->assertInstanceOf(SomeEvent::class, $event);

                $count++;
            },
            function (DomainEvent $event): never {
                $this->assertInstanceOf(SomeEvent::class, $event);

                throw new RuntimeException('stop at 1');
            },
            function (DomainEvent $event) use (&$count): void {
                $this->assertInstanceOf(SomeEvent::class, $event);

                $count++;
            },
        ];

        $story = $tracker->newStory(Reporter::DISPATCH_EVENT);

        $this->assertNull($story->consumers()->current());
        $this->assertFalse($story->isHandled());

        $story->withMessage(new Message($event));
        $story->withConsumers($consumers);

        $error = null;

        try {
            $tracker->disclose($story);
        } catch (Throwable $exception) {
            $error = $exception;
        }

        $this->assertInstanceOf(RuntimeException::class, $error);
        $this->assertEquals('stop at 1', $error->getMessage());
        $this->assertSame(1, $count);
        $this->assertFalse($story->isHandled());
    }

    public function testMarkMessageHandled(): void
    {
        $tracker = new TrackMessage();
        $subscriber = new ConsumeEvent();
        $subscriber->attachToReporter($tracker);

        $event = SomeEvent::fromContent([]);

        $consumers = [];

        $story = $tracker->newStory(Reporter::DISPATCH_EVENT);

        $this->assertNull($story->consumers()->current());
        $this->assertFalse($story->isHandled());

        $story->withMessage(new Message($event));
        $story->withConsumers($consumers);
        $tracker->disclose($story);

        $this->assertTrue($story->isHandled());
    }
}
