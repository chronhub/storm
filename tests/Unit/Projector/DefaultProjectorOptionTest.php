<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Projector\Options\DefaultProjectorOption;

final class DefaultProjectorOptionTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_assert_default_projector_option(): void
    {
        $options = new DefaultProjectorOption();

        $this->assertEquals(false, $options->getDispatchSignal());
        $this->assertEquals(1000, $options->getStreamCacheSize());
        $this->assertEquals(1000, $options->getPersistBlockSize());
        $this->assertEquals(1000, $options->getLockTimeoutMs());
        $this->assertEquals(100000, $options->getSleepBeforeUpdateLock());
        $this->assertEquals(100000, $options->getUpdateLockThreshold());
        $this->assertEquals([0, 5, 50, 100, 150, 200, 250], $options->getRetriesMs());
        $this->assertNull($options->getDetectionWindows());
    }

    /**
     * @test
     */
    public function it_promote_parameters(): void
    {
        $options = new DefaultProjectorOption(dispatchSignal: true, updateLockThreshold: 100, detectionWindows: 'PT1H');

        $this->assertEquals(true, $options->getDispatchSignal());
        $this->assertEquals(1000, $options->getStreamCacheSize());
        $this->assertEquals(1000, $options->getPersistBlockSize());
        $this->assertEquals(1000, $options->getLockTimeoutMs());
        $this->assertEquals(100000, $options->getSleepBeforeUpdateLock());
        $this->assertEquals(100, $options->getUpdateLockThreshold());
        $this->assertEquals([0, 5, 50, 100, 150, 200, 250], $options->getRetriesMs());
        $this->assertEquals('PT1H', $options->getDetectionWindows());
    }

    /**
     * @test
     */
    public function it_set_retries_ms_as_string(): void
    {
        $options = new DefaultProjectorOption(retriesMs: '5,50,10');

        $this->assertEquals([5, 15, 25, 35, 45], $options->retriesMs);

        $this->assertEquals($options->jsonSerialize()[$options::RETRIES_MS], $options->retriesMs);
    }

    /**
     * @test
     */
    public function it_can_be_serialized(): void
    {
        $options = new DefaultProjectorOption();

        $this->assertEquals([
            'dispatch_signal' => false,
            'stream_cache_size' => 1000,
            'persist_block_size' => 1000,
            'lock_timeout_ms' => 1000,
            'sleep_before_update_lock' => 100000,
            'update_lock_threshold' => 100000,
            'retries_ms' => [0, 5, 50, 100, 150, 200, 250],
            'detection_windows' => null,
        ], $options->jsonSerialize());
    }
}
