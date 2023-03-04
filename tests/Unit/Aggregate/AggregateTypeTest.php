<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Aggregate;

use stdClass;
use Generator;
use InvalidArgumentException;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Tests\Double\SomeEvent;
use Chronhub\Storm\Aggregate\AggregateType;
use Chronhub\Storm\Aggregate\V4AggregateId;
use PHPUnit\Framework\Attributes\DataProvider;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Tests\Stubs\AggregateRootStub;
use Chronhub\Storm\Tests\Stubs\AggregateRootChildStub;
use Chronhub\Storm\Tests\Stubs\AnotherAggregateRootStub;

final class AggregateTypeTest extends UnitTestCase
{
    #[Test]
    public function it_can_be_instantiated(): void
    {
        $aggregateType = new AggregateType(AnotherAggregateRootStub::class);

        $this->assertEquals(AnotherAggregateRootStub::class, $aggregateType->current());
    }

    #[Test]
    public function it_raise_exception_when_aggregate_root_is_not_a_valid_class_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Aggregate root must be a valid class name');

        /** @phpstan-ignore-next-line  */
        new AggregateType('invalid_class');
    }

    #[Test]
    public function it_raise_exception_when_lineage_are_not_subclass_of_aggregate_root(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Class '.stdClass::class.' must inherit from '.AnotherAggregateRootStub::class);

        new AggregateType(AnotherAggregateRootStub::class, [stdClass::class]);
    }

    #[Test]
    public function it_raise_exception_when_aggregate_root_is_not_supported(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Aggregate root '.AnotherAggregateRootStub::class.' class is not supported');

        $aggregateType = new AggregateType(AggregateRootStub::class, [AggregateRootChildStub::class]);

        $aggregateType->from(AnotherAggregateRootStub::class);
    }

    #[DataProvider('provideValidAggregateTypeHeader')]
    #[Test]
    public function it_support_aggregate_root(string $aggregateTypeHeader): void
    {
        $aggregateType = new AggregateType(
            AggregateRootStub::class, [AggregateRootChildStub::class]
        );

        $domainEvent = SomeEvent::fromContent([])
            ->withHeaders([EventHeader::AGGREGATE_TYPE => $aggregateTypeHeader]);

        $aggregateRoot = $aggregateType->from($domainEvent);

        $this->assertEquals($aggregateTypeHeader, $aggregateRoot);
    }

    #[Test]
    public function it_check_if_aggregate_root_is_supported(): void
    {
        $aggregateType = new AggregateType(AggregateRootStub::class, [AggregateRootChildStub::class]);

        $this->assertTrue($aggregateType->isSupported(AggregateRootChildStub::class));
    }

    #[Test]
    public function it_determine_type_from_aggregate_root_object(): void
    {
        $aggregateType = new AggregateType(AggregateRootStub::class, [AggregateRootChildStub::class]);

        $this->assertEquals(
            AggregateRootStub::class,
            $aggregateType->from(AggregateRootStub::create(V4AggregateId::create()))
        );

        $this->assertEquals(
            AggregateRootStub::class,
            $aggregateType->from(AggregateRootChildStub::create(V4AggregateId::create()))
        );
    }

    #[Test]
    public function it_determine_type_from_aggregate_root_string_class(): void
    {
        $aggregateType = new AggregateType(
            AggregateRootStub::class, [AggregateRootChildStub::class]
        );

        $this->assertEquals(
            AggregateRootStub::class,
            $aggregateType->from(AggregateRootStub::class)
        );

        $this->assertEquals(
            AggregateRootStub::class,
            $aggregateType->from(AggregateRootChildStub::class)
        );
    }

    public static function provideValidAggregateTypeHeader(): Generator
    {
        yield [AggregateRootStub::class];
        yield [AggregateRootChildStub::class];
    }
}
