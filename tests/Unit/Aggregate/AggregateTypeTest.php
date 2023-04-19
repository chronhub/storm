<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Aggregate;

use Chronhub\Storm\Aggregate\AggregateType;
use Chronhub\Storm\Aggregate\V4AggregateId;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Tests\Stubs\AggregateRootChildStub;
use Chronhub\Storm\Tests\Stubs\AggregateRootStub;
use Chronhub\Storm\Tests\Stubs\AnotherAggregateRootStub;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;
use function sprintf;

#[CoversClass(AggregateType::class)]
final class AggregateTypeTest extends UnitTestCase
{
    public function testInstance(): void
    {
        $aggregateType = new AggregateType(AnotherAggregateRootStub::class);

        $this->assertEquals(AnotherAggregateRootStub::class, $aggregateType->current());
    }

    public function testExceptionRaisedWhenAggregateIsNotFQN(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Aggregate root must be a valid class name');

        /** @phpstan-ignore-next-line  */
        new AggregateType('invalid_class');
    }

    public function testExceptionRaisedWhenAggregateIsNotSubclassOfRoot(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Class %s must inherit from %s', stdClass::class, AnotherAggregateRootStub::class)
        );

        new AggregateType(AnotherAggregateRootStub::class, [stdClass::class]);
    }

    public function testAggregateIsNotSupported(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Aggregate given Chronhub\Storm\Tests\Stubs\AnotherAggregateRootStub is not supported');

        $aggregateType = new AggregateType(AggregateRootStub::class, [AggregateRootChildStub::class]);

        $aggregateType->from(AnotherAggregateRootStub::class);
    }

    #[DataProvider('provideValidAggregateTypeHeader')]
    public function testAggregateIsSupported(string $aggregateTypeHeader): void
    {
        $aggregateType = new AggregateType(
            AggregateRootStub::class, [AggregateRootChildStub::class]
        );

        $domainEvent = SomeEvent::fromContent([])
            ->withHeaders([EventHeader::AGGREGATE_TYPE => $aggregateTypeHeader]);

        $aggregateRoot = $aggregateType->from($domainEvent);

        $this->assertEquals($aggregateTypeHeader, $aggregateRoot);
    }

    public function testAggregateChildIsSupported(): void
    {
        $aggregateType = new AggregateType(AggregateRootStub::class, [AggregateRootChildStub::class]);

        $this->assertTrue($aggregateType->isSupported(AggregateRootChildStub::class));
    }

    public function testAggregateIsDetermineFromRootInstance(): void
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

    public function testAggregateIsDetermineFromRootString(): void
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
