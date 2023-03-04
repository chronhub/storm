<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Stream;

use Generator;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Chronhub\Storm\Stream\DetermineStreamCategory;

#[CoversClass(DetermineStreamCategory::class)]
final class DetermineStreamCategoryTest extends UnitTestCase
{
    #[Test]
    public function it_can_be_instantiated_with_default_dash_separator(): void
    {
        $detectCategory = new DetermineStreamCategory();

        $this->assertEquals('-', $detectCategory->separator);
    }

    #[DataProvider('provideStreamCategory')]
    #[Test]
    public function it_detect_category_from_stream_name(string $streamName): void
    {
        $detectCategory = new DetermineStreamCategory();

        $this->assertEquals('transaction', $detectCategory($streamName));
    }

    #[Test]
    public function it_only_detect_first_separator_to_determine_category_from_stream_name(): void
    {
        $detectCategory = new DetermineStreamCategory();

        $this->assertEquals('transaction', $detectCategory('transaction-add-absolute'));
    }

    #[DataProvider('provideStreamWithoutCategory')]
    #[Test]
    public function it_return_null_when_category_from_stream_name_can_not_be_detected(string $streamName): void
    {
        $detectCategory = new DetermineStreamCategory();

        $this->assertNull($detectCategory($streamName));
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
