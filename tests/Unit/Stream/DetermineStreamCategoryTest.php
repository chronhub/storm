<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Stream;

use Generator;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Stream\DetermineStreamCategory;

final class DetermineStreamCategoryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_can_be_instantiated_with_default_dash_separator(): void
    {
        $detectCategory = new DetermineStreamCategory();

        $this->assertEquals('-', $detectCategory->separator);
    }

    /**
     * @test
     *
     * @dataProvider provideStreamCategory
     */
    public function it_detect_category_from_stream_name(string $streamName): void
    {
        $detectCategory = new DetermineStreamCategory();

        $this->assertEquals('transaction', $detectCategory($streamName));
    }

    /**
     * @test
     */
    public function it_only_detect_first_separator_to_determine_category_from_stream_name(): void
    {
        $detectCategory = new DetermineStreamCategory();

        $this->assertEquals('transaction', $detectCategory('transaction-add-absolute'));
    }

    /**
     * @test
     *
     * @dataProvider provideStreamWithoutCategory
     */
    public function it_return_null_when_category_from_stream_name_can_not_be_detected(string $streamName): void
    {
        $detectCategory = new DetermineStreamCategory();

        $this->assertNull($detectCategory($streamName));
    }

    public function provideStreamCategory(): Generator
    {
        yield ['transaction-add'];
        yield ['transaction-subtract'];
        yield ['transaction-divide'];
    }

    public function provideStreamWithoutCategory(): Generator
    {
        yield ['transaction'];
        yield ['transaction_subtract'];
        yield ['transaction|subtract'];
    }
}
