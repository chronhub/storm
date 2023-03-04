<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Projector\Pipes\DispatchSignal;
use Chronhub\Storm\Tests\Unit\Projector\Util\ProvideMockContext;
use function posix_kill;
use function pcntl_signal;
use function posix_getpid;
use function extension_loaded;

final class DispatchSignalTest extends UnitTestCase
{
    use ProvideMockContext;

    /**
     * @test
     */
    public function it_dispatch_signal(): void
    {
        if (! extension_loaded('posix')) {
            $this->markTestSkipped('Extension posix not available');
        }

        $this->option->expects($this->once())
            ->method('getSignal')
            ->willReturn(true);

        $result = null;

        pcntl_signal(SIGHUP, function () use (&$result) {
            $result = 'signal handler dispatched';
        });

        posix_kill(posix_getpid(), SIGHUP);

        $pipe = new DispatchSignal();

        $pipe($this->newContext(), fn (Context $context): bool => true);

        $this->assertEquals('signal handler dispatched', $result);
    }

    /**
     * @test
     */
    public function it_does_not_dispatch_signal(): void
    {
        if (! extension_loaded('posix')) {
            $this->markTestSkipped('Extension posix not available');
        }

        $this->option->expects($this->once())
            ->method('getSignal')
            ->willReturn(false);

        $result = null;

        pcntl_signal(SIGHUP, function () use (&$result) {
            $result = 'signal handler dispatched';
        });

        posix_kill(posix_getpid(), SIGHUP);

        $pipe = new DispatchSignal();

        $pipe($this->newContext(), fn (Context $context): bool => true);

        $this->assertNull($result);
    }
}
