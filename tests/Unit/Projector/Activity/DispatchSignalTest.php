<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Activity;

use Generator;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\Activity\DispatchSignal;
use Chronhub\Storm\Projector\Options\DefaultProjectionOption;
use function posix_kill;
use function pcntl_signal;
use function posix_getpid;

final class DispatchSignalTest extends UnitTestCase
{
    #[DataProvider('provideBoolean')]
    public function testPCNTLSignalDispatch(bool $dispatchSignal): void
    {
        $options = new DefaultProjectionOption(signal: $dispatchSignal);

        $subscription = $this->createMock(Subscription::class);

        $subscription->expects($this->once())->method('option')->willReturn($options);

        $activity = new DispatchSignal();

        $called = false;
        pcntl_signal(SIGTERM, function () use (&$called) {
            $called = true;
        });

        posix_kill(posix_getpid(), SIGTERM);

        $activity($subscription, fn () => true);

        $this->assertSame($dispatchSignal, $called);
    }

    public static function provideBoolean(): Generator
    {
        yield [true];
        yield [false];
    }
}