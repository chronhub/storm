<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Options;

use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Projector\Options\InMemorySubscriptionOption;

#[CoversClass(InMemorySubscriptionOption::class)]
final class InMemoryProjectorOptionTest extends UnitTestCase
{
    #[Test]
    public function it_assert_in_memory_immutable_option(): void
    {
        $options = new InMemorySubscriptionOption();

        $this->assertEquals(false, $options->getSignal());
        $this->assertEquals(100, $options->getCacheSize());
        $this->assertEquals(1, $options->getBlockSize());
        $this->assertEquals(0, $options->getTimeout());
        $this->assertEquals(100, $options->getSleep());
        $this->assertEquals(0, $options->getLockout());
        $this->assertEmpty($options->getRetries());
        $this->assertNull($options->getDetectionWindows());
    }

    #[Test]
    public function it_can_be_serialized(): void
    {
        $options = new InMemorySubscriptionOption();

        $this->assertEquals([
            'signal' => false,
            'cacheSize' => 100,
            'blockSize' => 1,
            'timeout' => 0,
            'sleep' => 100,
            'lockout' => 0,
            'retries' => [],
            'detectionWindows' => null,
        ], $options->jsonSerialize());
    }
}
