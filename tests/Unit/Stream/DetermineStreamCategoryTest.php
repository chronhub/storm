<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Stream;

use Generator;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Chronhub\Storm\Stream\DetermineStreamCategory;

#[CoversClass(DetermineStreamCategory::class)]
final class DetermineStreamCategoryTest extends UnitTestCase
{
    public function testInstanceWithDefaultSeparator(): void
    {
        $detectCategory = new DetermineStreamCategory();

        $this->assertEquals('-', $detectCategory->separator);
    }

    #[DataProvider('provideStreamCategory')]
    public function TestCategoryDetected(string $streamName): void
    {
        $detectCategory = new DetermineStreamCategory();

        $this->assertEquals('transaction', $detectCategory->determineFrom($streamName));
    }

    public function testDetectCategoryComposedWithFirstSeparator(): void
    {
        $detectCategory = new DetermineStreamCategory();

        $this->assertEquals('transaction', $detectCategory->determineFrom('transaction-add-absolute'));
    }

    #[DataProvider('provideStreamWithoutCategory')]
    public function testCategoryNotDetected(string $streamName): void
    {
        $detectCategory = new DetermineStreamCategory();

        $this->assertNull($detectCategory->determineFrom($streamName));
    }

    public static function provideStreamCategory(): Generator
    {
        yield ['transaction-add'];
        yield ['transaction-subtract'];
        yield ['transaction-divide'];
    }

    public static function provideStreamWithoutCategory(): Generator
    {
        yield ['transaction'];
        yield ['transaction_subtract'];
        yield ['transaction|subtract'];
    }
}
