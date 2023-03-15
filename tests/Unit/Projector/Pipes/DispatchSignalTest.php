<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Pipes;

use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Projector\Scheme\Context;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Projector\Pipes\DispatchSignal;
use Chronhub\Storm\Tests\Unit\Projector\Mock\ProvideMockContext;
use function posix_kill;
use function pcntl_signal;
use function posix_getpid;
use function extension_loaded;

#[CoversClass(DispatchSignal::class)]
final class DispatchSignalTest extends UnitTestCase
{
    use ProvideMockContext;

    #[Test]
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

    #[Test]
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
