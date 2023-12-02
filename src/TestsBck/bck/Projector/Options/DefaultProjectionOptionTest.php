<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Options;

use Chronhub\Storm\Projector\Options\DefaultOption;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DefaultOption::class)]
final class DefaultProjectionOptionTest extends UnitTestCase
{
    public function testDefaultValues()
    {
        $options = new DefaultOption();

        $expectedResult = [
            'signal' => false,
            'cacheSize' => 1000,
            'blockSize' => 1000,
            'sleep' => 100000,
            'timeout' => 1000,
            'lockout' => 100000,
            'retries' => [0, 5, 50, 100, 150, 200, 250, 500, 750, 1000],
            'detectionWindows' => null,
        ];

        $this->assertEquals($expectedResult, $options->jsonSerialize());
    }

    public function testPromoteConstructor()
    {
        $options = new DefaultOption(signal: true, lockout: 0);

        $this->assertTrue($options->jsonSerialize()['signal']);
        $this->assertSame(0, $options->jsonSerialize()['lockout']);
    }
}
