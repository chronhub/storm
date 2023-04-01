<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Repository;

use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Projector\Repository\LockManager;
use function sleep;

#[CoversClass(LockManager::class)]
final class LockManagerTest extends UnitTestCase
{
    private LockManager $lockManager;

    protected function setUp(): void
    {
        $this->lockManager = new LockManager(new PointInTime(), 1000, 1000);
    }

    public function testAcquire(): void
    {
        $lock = $this->lockManager->acquire();

        $this->assertIsString($lock);
    }

    public function testTryUpdate(): void
    {
        $this->lockManager->acquire();

        $updated = $this->lockManager->tryUpdate();

        $this->assertFalse($updated);

        sleep(2);

        $updated = $this->lockManager->tryUpdate();

        $this->assertTrue($updated);
    }

    public function testRefresh(): void
    {
        $this->lockManager->acquire();

        sleep(1);

        $lock = $this->lockManager->refresh();

        $this->assertIsString($lock);
    }

    public function testIncrement(): void
    {
        $this->lockManager->acquire();

        sleep(1);

        $lock = $this->lockManager->increment();

        $this->assertIsString($lock);
    }

    public function testCurrent(): void
    {
        $this->lockManager->acquire();

        $current = $this->lockManager->current();

        $this->assertIsString($current);
    }

    public function testAlwaysUpdateLockWithThresholdIsZero(): void
    {
        $lockManager = new LockManager(new PointInTime(), 1000, 0);

        $this->lockManager->acquire();

        $this->assertTrue($lockManager->tryUpdate());
    }
}
