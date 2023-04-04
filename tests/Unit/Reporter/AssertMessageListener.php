<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Reporter;

use Chronhub\Storm\Contracts\Tracker\MessageSubscriber;
use Chronhub\Storm\Tests\Util\ReflectionProperty;
use Chronhub\Storm\Tracker\TrackMessage;
use PHPUnit\Framework\TestCase;

final class AssertMessageListener
{
    public static function isTrackedAndCanBeUntracked(MessageSubscriber $subscriber,
                                                      string $eventName,
                                                      int $priority): void
    {
        $tracker = new TrackMessage();

        TestCase::assertEmpty($tracker->listeners());
        TestCase::assertEmpty(ReflectionProperty::getProperty($subscriber, 'messageListeners'));

        $subscriber->attachToReporter($tracker);

        TestCase::assertCount(1, $tracker->listeners());
        TestCase::assertCount(1, ReflectionProperty::getProperty($subscriber, 'messageListeners'));

        $listener = $tracker->listeners()->first();

        TestCase::assertEquals($priority, $listener->eventPriority);
        TestCase::assertEquals($eventName, $listener->eventName);

        $subscriber->detachFromReporter($tracker);

        TestCase::assertCount(0, $tracker->listeners());
        TestCase::assertEmpty(ReflectionProperty::getProperty($subscriber, 'messageListeners'));
    }
}
