<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Chronhub\Storm\Tests\ProphecyTestCase;
use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Projector\Pipes\PersistOrUpdateLock;
use Chronhub\Storm\Tests\Unit\Projector\Util\ProvideContextWithProphecy;

final class PersistOrUpdateLockTest extends ProphecyTestCase
{
    use ProvideContextWithProphecy;

    /**
     * @test
     */
    public function it_return_next_context_when_gap_is_detected(): void
    {
        $context = $this->newContext();

        $this->gap->hasGap()->willReturn(true)->shouldBeCalledonce();
        $this->gap->sleep()->shouldBeCalledonce();

        $pipe = new PersistOrUpdateLock($this->repository->reveal());

        $run = $pipe($context, fn (Context $context): bool => true);

        $this->assertTrue($run);
    }

    /**
     * @test
     */
    public function it_sleep_before_updating_lock_if_event_counter_is_reset(): void
    {
        $this->repository->renew()->shouldBeCalledOnce();

        $context = $this->newContext();
        $this->gap->hasGap()->willReturn(false)->shouldBeCalledOnce();

        $this->option->getSleep()->willReturn(1000)->shouldBeCalledOnce();

        $this->counter->isReset()->willReturn(true)->shouldBeCalledonce();

        $pipe = new PersistOrUpdateLock($this->repository->reveal());

        $run = $pipe($context, fn (Context $context): bool => true);

        $this->assertTrue($run);
    }

    /**
     * @test
     */
    public function it_persist_if_event_counter_is_not_reset(): void
    {
        $this->repository->store()->shouldBeCalledonce();

        $context = $this->newContext();

        $this->gap->hasGap()->willReturn(false)->shouldBeCalledonce();

        $this->counter->isReset()->willReturn(false)->shouldBeCalledonce();

        $pipe = new PersistOrUpdateLock($this->repository->reveal());

        $run = $pipe($context, fn (Context $context): bool => true);

        $this->assertTrue($run);
    }
}
