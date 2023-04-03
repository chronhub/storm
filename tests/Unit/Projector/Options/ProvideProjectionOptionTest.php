<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Options;

use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Projector\Options\ProvideProjectionOption;

#[CoversClass(ProvideProjectionOption::class)]
final class ProvideProjectionOptionTest extends UnitTestCase
{
    public function testAccessor()
    {
        $options = new class implements ProjectionOption
        {
            use ProvideProjectionOption;

            public function __construct()
            {
                $this->signal = true;
                $this->cacheSize = 10;
                $this->timeout = 5000;
                $this->sleep = 500;
                $this->blockSize = 100;
                $this->lockout = 10000;
                $this->setUpRetries([10, 20, 30, 40, 50]);
                $this->detectionWindows = null;
            }
        };

        $this->assertSame(true, $options->signal);
        $this->assertSame(true, $options->getSignal());
        $this->assertSame(10, $options->cacheSize);
        $this->assertSame(10, $options->getCacheSize());
        $this->assertSame(5000, $options->timeout);
        $this->assertSame(5000, $options->getTimeout());
        $this->assertSame(100, $options->blockSize);
        $this->assertSame(100, $options->getBlockSize());
        $this->assertSame(10000, $options->lockout);
        $this->assertSame(10000, $options->getLockout());
        $this->assertSame([10, 20, 30, 40, 50], $options->retries);
        $this->assertSame([10, 20, 30, 40, 50], $options->getRetries());
        $this->assertNull($options->detectionWindows);
        $this->assertNull($options->getDetectionWindows());
    }

    public function testJsonSerialize()
    {
        $options = new class implements ProjectionOption
        {
            use ProvideProjectionOption;

            public function __construct()
            {
                $this->signal = true;
                $this->cacheSize = 10;
                $this->timeout = 5000;
                $this->sleep = 500;
                $this->blockSize = 100;
                $this->lockout = 10000;
                $this->setUpRetries('100, 500, 100');
                $this->detectionWindows = 'PT10S';
            }
        };

        $expectedResult = [
            'signal' => true,
            'cacheSize' => 10,
            'blockSize' => 100,
            'timeout' => 5000,
            'sleep' => 500,
            'lockout' => 10000,
            'retries' => [100, 200, 300, 400, 500],
            'detectionWindows' => 'PT10S',
        ];

        $this->assertEquals($expectedResult, $options->jsonSerialize());
    }
}
