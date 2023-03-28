<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Options;

use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Projector\Options\ProjectionOption;

#[CoversClass(ProjectionOption::class)]
final class DefaultProjectorOptionTest extends UnitTestCase
{
    #[Test]
    public function it_assert_default_projector_option(): void
    {
        $options = new ProjectionOption();

        $this->assertEquals(false, $options->getSignal());
        $this->assertEquals(1000, $options->getCacheSize());
        $this->assertEquals(1000, $options->getBlockSize());
        $this->assertEquals(1000, $options->getTimeout());
        $this->assertEquals(100000, $options->getSleep());
        $this->assertEquals(100000, $options->getLockout());
        $this->assertEquals([0, 5, 50, 100, 150, 200, 250], $options->getRetries());
        $this->assertNull($options->getDetectionWindows());
    }

    #[Test]
    public function it_promote_parameters(): void
    {
        $options = new ProjectionOption(signal: true, lockout: 100, detectionWindows: 'PT1H');

        $this->assertEquals(true, $options->getSignal());
        $this->assertEquals(1000, $options->getCacheSize());
        $this->assertEquals(1000, $options->getBlockSize());
        $this->assertEquals(1000, $options->getTimeout());
        $this->assertEquals(100000, $options->getSleep());
        $this->assertEquals(100, $options->getLockout());
        $this->assertEquals([0, 5, 50, 100, 150, 200, 250], $options->getRetries());
        $this->assertEquals('PT1H', $options->getDetectionWindows());
    }

    #[Test]
    public function it_set_retries_ms_as_string(): void
    {
        $options = new ProjectionOption(retries: '5,50,10');

        $this->assertEquals([5, 15, 25, 35, 45], $options->retries);

        $this->assertEquals($options->jsonSerialize()[$options::RETRIES], $options->retries);
    }

    #[Test]
    public function it_can_be_serialized(): void
    {
        $options = new ProjectionOption();

        $this->assertEquals([
            'signal' => false,
            'cacheSize' => 1000,
            'blockSize' => 1000,
            'timeout' => 1000,
            'sleep' => 100000,
            'lockout' => 100000,
            'retries' => [0, 5, 50, 100, 150, 200, 250],
            'detectionWindows' => null,
        ], $options->jsonSerialize());
    }
}
