<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Chronhub\Storm\Projector\Activity\ResetEventCounter;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ResetEventCounter::class)]
final class ResetEventCounterTest extends UnitTestCase
{
    public function testActivity(): void
    {
        $subscription = $this->createMock(PersistentSubscriptionInterface::class);

        $eventCounter = new EventCounter(5);
        $eventCounter->increment();
        $eventCounter->increment();
        $this->assertSame(2, $eventCounter->current());

        $subscription->expects($this->exactly(2))->method('eventCounter')->willReturn($eventCounter);

        $activity = new ResetEventCounter();

        $next = function (PersistentSubscriptionInterface $subscription) {
            return $subscription->eventCounter()->isReset();
        };

        $this->assertTrue($activity($subscription, $next));
    }
}
