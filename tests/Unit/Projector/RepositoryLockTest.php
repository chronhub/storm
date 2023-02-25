<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use DateInterval;
use Chronhub\Storm\Clock\PointInTime;
use Prophecy\Prophecy\ObjectProphecy;
use Chronhub\Storm\Tests\ProphecyTestCase;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Projector\Repository\RepositoryLock;
use function sleep;
use function usleep;

final class RepositoryLockTest extends ProphecyTestCase
{
    private SystemClock|ObjectProphecy $clock;

    private SystemClock|PointInTime $time;

    protected function setUp(): void
    {
        $this->clock = $this->prophesize(SystemClock::class);
        $this->time = new PointInTime();
    }

    /**
     * @test
     */
    public function it_can_be_instantiated(): void
    {
        $lock = new RepositoryLock($this->clock->reveal(), 1000, 1000);

        $this->assertNull($lock->current());
    }

    /**
     * @test
     */
    public function it_acquire_lock(): void
    {
        $datetime = $this->time->now();

        $this->clock->getFormat()->willReturn($this->time::DATE_TIME_FORMAT);
        $this->clock->now()->willReturn($datetime)->shouldBeCalled();

        $lock = new RepositoryLock($this->clock->reveal(), 1000, 1000);

        $this->assertNull($lock->current());

        $lockUntil = $lock->acquire();
        $updatedTime = $datetime->add(new DateInterval('PT1S'));

        $this->assertEquals($updatedTime->format($this->time->getFormat()), $lock->update());
        $this->assertEquals($updatedTime->format($this->time->getFormat()), $lockUntil);
    }

    /**
     * @test
     */
    public function it_always_update_lock_when_last_lock_update_is_not_fixed(): void
    {
        $datetime = $this->time->now();

        $this->clock->now()->willReturn($datetime)->shouldBeCalled();
        $this->clock->getFormat()->willReturn($this->time::DATE_TIME_FORMAT);

        $lock = new RepositoryLock($this->clock->reveal(), 1000, 1000);

        $this->assertNull($lock->current());

        usleep(5);

        $this->assertTrue($lock->tryUpdate());

        $this->assertEquals($datetime->format($this->time->getFormat()), $lock->current());
    }

    /**
     * @test
     */
    public function it_always_update_lock_when_lock_threshold_is_zero(): void
    {
        $datetime = $this->time->now();

        $this->clock->now()->willReturn($datetime)->shouldBeCalled();
        $this->clock->getFormat()->willReturn($this->time::DATE_TIME_FORMAT)->shouldBeCalled();

        $lock = new RepositoryLock($this->clock->reveal(), 1000, 0);

        $this->assertNull($lock->current());

        $this->assertTrue($lock->tryUpdate());

        $this->assertEquals($datetime->format($this->time->getFormat()), $lock->current());
    }

    /**
     * @test
     */
    public function it_update_lock_when_incremented_last_lock_is_less_than_last_lock_updated(): void
    {
        $lock = new RepositoryLock($this->time, 1000, 1000);

        $lock->acquire();

        $this->assertFalse($lock->tryUpdate());

        sleep(1);

        $this->assertTrue($lock->tryUpdate());
    }

    /**
     * @test
     */
    public function it_update_lock_when_incremented_last_lock_is_greater_than_last_lock_updated(): void
    {
        $lock = new RepositoryLock($this->time, 1000, 1000);

        $lock->acquire();

        $this->assertFalse($lock->tryUpdate());
    }

    /**
     * @test
     */
    public function it_return_refresh_last_lock_update_with_lock_timeout_ms(): void
    {
        $datetime = $this->time->now();

        $this->clock->now()->willReturn($datetime)->shouldBeCalled();
        $this->clock->getFormat()->willReturn($this->time::DATE_TIME_FORMAT)->shouldBeCalled();

        $lock = new RepositoryLock($this->clock->reveal(), 1000, 1000);

        $timeoutAdded = $datetime->add(new DateInterval('PT1S'));

        $this->assertEquals($timeoutAdded->format($this->time->getFormat()), $lock->refresh());
    }
}
