<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Reporter;

use Chronhub\Storm\Contracts\Reporter\Reporter;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Reporter\Exceptions\MessageCollectedException;
use Chronhub\Storm\Reporter\OnDispatchPriority;
use Chronhub\Storm\Reporter\Subscribers\TryConsumeEvent;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Tracker\TrackMessage;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;

#[CoversClass(TryConsumeEvent::class)]
final class TryConsumeEventTest extends UnitTestCase
{
    public function testSubscriber(): void
    {
        $subscriber = new TryConsumeEvent();

        AssertMessageListener::isTrackedAndCanBeUntracked(
            $subscriber,
            Reporter::DISPATCH_EVENT,
            OnDispatchPriority::INVOKE_HANDLER->value
        );
    }

    public function testCollectExceptionsWhileConsuming(): void
    {
        $tracker = new TrackMessage();
        $subscriber = new TryConsumeEvent();
        $subscriber->attachToReporter($tracker);

        $event = SomeEvent::fromContent([]);

        $count = 0;

        $consumers = [
            function (DomainEvent $event): never {
                $this->assertInstanceOf(SomeEvent::class, $event);

                throw new RuntimeException('first exception');
            },
            function (DomainEvent $event) use (&$count): void {
                $this->assertInstanceOf(SomeEvent::class, $event);

                $count += 1;
            },
            function (DomainEvent $event): never {
                $this->assertInstanceOf(SomeEvent::class, $event);

                throw new RuntimeException('last exception');
            },
            function (DomainEvent $event) use (&$count): void {
                $this->assertInstanceOf(SomeEvent::class, $event);

                $count += 1;
            },
        ];

        $story = $tracker->newStory(Reporter::DISPATCH_EVENT);

        $this->assertNull($story->consumers()->current());
        $this->assertFalse($story->isHandled());

        $story->withMessage(new Message($event));
        $story->withConsumers($consumers);

        $tracker->disclose($story);

        $this->assertTrue($story->hasException());

        $collectedException = $story->exception();

        $this->assertInstanceOf(MessageCollectedException::class, $collectedException);

        $this->assertCount(2, $collectedException->getExceptions());

        $this->assertEquals([
            new RuntimeException('first exception'),
            new RuntimeException('last exception'),
        ], $collectedException->getExceptions());

        $this->assertSame(2, $count);

        $this->assertTrue($story->isHandled());
    }
}
