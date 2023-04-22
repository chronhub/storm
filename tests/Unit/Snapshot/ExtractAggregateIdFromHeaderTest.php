<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Snapshot;

use Chronhub\Storm\Aggregate\V4AggregateId;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Snapshot\ExtractAggregateIdFromHeader;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use Error;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use TypeError;

#[CoversClass(ExtractAggregateIdFromHeader::class)]
class ExtractAggregateIdFromHeaderTest extends UnitTestCase
{
    public function testExtractAggregateId(): void
    {
        $event = SomeEvent::fromContent(['foo' => 'bar'])
            ->withHeader(EventHeader::AGGREGATE_ID, '97a1b3bd-c811-4a7a-8308-fab6505080b1')
            ->withHeader(EventHeader::AGGREGATE_ID_TYPE, V4AggregateId::class);

        $aggregateId = $this->newInstance()->call($event);

        $this->assertInstanceOf(V4AggregateId::class, $aggregateId);
        $this->assertEquals('97a1b3bd-c811-4a7a-8308-fab6505080b1', $aggregateId->toString());
    }

    public function testExtractSameAggregateIdInstance(): void
    {
        $aggregateId = V4AggregateId::fromString('97a1b3bd-c811-4a7a-8308-fab6505080b1');

        $event = SomeEvent::fromContent(['foo' => 'bar'])
            ->withHeader(EventHeader::AGGREGATE_ID, $aggregateId)
            ->withHeader(EventHeader::AGGREGATE_ID_TYPE, V4AggregateId::class);

        $aggregateIdExpected = $this->newInstance()->call($event);

        $this->assertInstanceOf(V4AggregateId::class, $aggregateIdExpected);
        $this->assertEquals('97a1b3bd-c811-4a7a-8308-fab6505080b1', $aggregateIdExpected->toString());
        $this->assertSame($aggregateIdExpected, $aggregateId);
    }

    public function testErrorExceptionRaised(): void
    {
        $event = SomeEvent::fromContent(['foo' => 'bar'])
            ->withHeader(EventHeader::AGGREGATE_ID, '97a1b3bd-c811-4a7a-8308-fab6505080b1');

        $this->expectException(Error::class);
        $this->expectExceptionMessage('Class name must be a valid object or a string');

        $this->newInstance()->call($event);
    }

    public function testTypeErrorExceptionRaised(): void
    {
        $event = SomeEvent::fromContent(['foo' => 'bar'])
            ->withHeader(EventHeader::AGGREGATE_ID_TYPE, V4AggregateId::class);

        $this->expectException(TypeError::class);

        $this->newInstance()->call($event);
    }

    public function testInvalidUUIDExceptionRaised(): void
    {
        $event = SomeEvent::fromContent(['foo' => 'bar'])
            ->withHeader(EventHeader::AGGREGATE_ID, 'foo')
            ->withHeader(EventHeader::AGGREGATE_ID_TYPE, V4AggregateId::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid UUID: "foo".');

        $this->newInstance()->call($event);
    }

    private function newInstance(): object
    {
        return new class()
        {
            use ExtractAggregateIdFromHeader;

            public function call($event): AggregateIdentity
            {
                return $this->extractAggregateId($event);
            }
        };
    }
}
