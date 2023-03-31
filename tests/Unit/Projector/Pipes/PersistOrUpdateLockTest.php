<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Pipes;

use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Projector\Scheme\Context;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Projector\Activity\PersistOrUpdateLock;
use Chronhub\Storm\Tests\Unit\Projector\Mock\ProvideMockContext;

#[CoversClass(PersistOrUpdateLock::class)]
final class PersistOrUpdateLockTest extends UnitTestCase
{
    use ProvideMockContext;

    #[Test]
    public function it_sleep_before_updating_lock_if_event_counter_is_reset(): void
    {
        $this->repository->expects($this->once())->method('renew');

        $context = $this->newContext();

        $this->gap->expects($this->once())->method('hasGap')->willReturn(false);
        $this->option->expects($this->once())->method('getSleep')->willReturn(1000);
        $this->counter->expects($this->once())->method('isReset')->willReturn(true);

        $pipe = new PersistOrUpdateLock($this->repository);

        $run = $pipe($context, fn (Context $context): bool => true);

        $this->assertTrue($run);
    }

    #[Test]
    public function it_persist_if_event_counter_is_not_reset(): void
    {
        $this->repository->expects($this->once())->method('store');

        $context = $this->newContext();

        $this->gap->expects($this->once())->method('hasGap')->willReturn(false);
        $this->counter->expects($this->once())->method('isReset')->willReturn(false);

        $pipe = new PersistOrUpdateLock($this->repository);

        $run = $pipe($context, fn (Context $context): bool => true);

        $this->assertTrue($run);
    }
}
