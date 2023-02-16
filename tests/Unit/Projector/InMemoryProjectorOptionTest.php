<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Projector\Options\InMemoryProjectorOption;

final class InMemoryProjectorOptionTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_assert_in_memory_immutable_option(): void
    {
        $options = new InMemoryProjectorOption();

        $this->assertEquals(false, $options->getDispatchSignal());
        $this->assertEquals(100, $options->getStreamCacheSize());
        $this->assertEquals(1, $options->getPersistBlockSize());
        $this->assertEquals(0, $options->getLockTimeoutMs());
        $this->assertEquals(100, $options->getSleepBeforeUpdateLock());
        $this->assertEquals(0, $options->getUpdateLockThreshold());
        $this->assertEmpty($options->getRetriesMs());
        $this->assertNull($options->getDetectionWindows());
    }

    /**
     * @test
     */
    public function it_can_be_serialized(): void
    {
        $options = new InMemoryProjectorOption();

        $this->assertEquals([
            'dispatch_signal' => false,
            'stream_cache_size' => 100,
            'persist_block_size' => 1,
            'lock_timeout_ms' => 0,
            'sleep_before_update_lock' => 100,
            'update_lock_threshold' => 0,
            'retries_ms' => [],
            'detection_windows' => null,
        ], $options->jsonSerialize());
    }
}
