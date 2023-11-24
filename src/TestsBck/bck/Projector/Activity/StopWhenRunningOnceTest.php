<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\PersistentProjector;
use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\Activity\StopWhenRunningOnce;
use Chronhub\Storm\Projector\Scheme\Sprint;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(StopWhenRunningOnce::class)]
final class StopWhenRunningOnceTest extends UnitTestCase
{
    public function testExplicitlyStopProjectionWhileNotRunningInBackground(): void
    {
        $subscription = $this->createMock(Subscription::class);

        $sprint = new Sprint();
        $this->assertFalse($sprint->inProgress());
        $this->assertFalse($sprint->inBackground());

        $sprint->continue();

        $this->assertTrue($sprint->inProgress());

        $subscription
            ->expects(self::exactly(3))
            ->method('sprint')
            ->willReturn($sprint);

        $next = function (Subscription $subscription) {
           return $subscription->sprint()->inProgress();
        };

        $projector = $this->createMock(PersistentProjector::class);
        $projector->expects(self::once())->method('stop');

        $stopWhenRunningOnce = new StopWhenRunningOnce($projector);

        $this->assertTrue($stopWhenRunningOnce($subscription, $next));
    }

    public function testDoesNotStopProjectionWhileRunningInBackground(): void
    {
        $subscription = $this->createMock(Subscription::class);

        $sprint = new Sprint();
        $this->assertFalse($sprint->inProgress());
        $this->assertFalse($sprint->inBackground());

        $sprint->continue();
        $sprint->runInBackground(true);

        $this->assertTrue($sprint->inProgress());
        $this->assertTrue($sprint->inBackground());

        $subscription
            ->expects(self::exactly(2))
            ->method('sprint')
            ->willReturn($sprint);

        $next = function (Subscription $subscription) {
            return $subscription->sprint()->inProgress();
        };

        $projector = $this->createMock(PersistentProjector::class);
        $projector->expects(self::never())->method('stop');

        $stopWhenRunningOnce = new StopWhenRunningOnce($projector);

        $this->assertTrue($stopWhenRunningOnce($subscription, $next));
    }
}
