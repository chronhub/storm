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
            'dispatchSignal' => false,
            'streamCacheSize' => 100,
            'persistBlockSize' => 1,
            'lockTimeoutMs' => 0,
            'sleepBeforeUpdateLock' => 100,
            'updateLockThreshold' => 0,
            'retriesMs' => [],
            'detectionWindows' => null,
        ], $options->jsonSerialize());
    }
}
