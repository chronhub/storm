<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Reporter;

use Throwable;
use RuntimeException;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Tracker\TrackMessage;
use Chronhub\Storm\Tests\Double\SomeEvent;
use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Reporter\OnDispatchPriority;
use Chronhub\Storm\Reporter\Subscribers\ConsumeEvent;

final class ConsumeEventTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_test_subscriber(): void
    {
        $subscriber = new ConsumeEvent();

        AssertMessageListener::isTrackedAndCanBeUntracked(
            $subscriber,
            Reporter::DISPATCH_EVENT,
            OnDispatchPriority::INVOKE_HANDLER->value
        );
    }

    /**
     * @test
     */
    public function it_consume_event_with_many_consumers(): void
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

    /**
     * @test
     */
    public function it_stop_consume_at_first_exception_and_does_not_mark_message_handled_even_if_one_has_been_consumed(): void
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
            function (DomainEvent $event): void {
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

    /**
     * @test
     */
    public function it_mark_message_handled_even_when_consumers_are_empty(): void
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
