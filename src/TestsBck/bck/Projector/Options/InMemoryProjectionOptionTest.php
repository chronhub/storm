<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Options;

use Chronhub\Storm\Projector\Options\InMemoryOption;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(InMemoryOption::class)]
final class InMemoryProjectionOptionTest extends UnitTestCase
{
    public function testInstance(): void
    {
        $options = new InMemoryOption();

        $expectedResult = [
            'signal' => false,
            'cacheSize' => 100,
            'blockSize' => 1,
            'sleep' => 100,
            'timeout' => 0,
            'lockout' => 0,
            'retries' => [],
            'detectionWindows' => null,
        ];

        $this->assertEquals($expectedResult, $options->jsonSerialize());
    }
}
