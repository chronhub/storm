<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Snapshot;

use Chronhub\Storm\Aggregate\V4AggregateId;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Snapshot\MatchAggregateGreaterThanVersion;
use Chronhub\Storm\Tests\Stubs\AggregateRootStub;
use Chronhub\Storm\Tests\Stubs\AnotherAggregateRootStub;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use function array_filter;

#[CoversClass(MatchAggregateGreaterThanVersion::class)]
final class MatchAggregateGreaterThanVersionTest extends UnitTestCase
{
    private V4AggregateId $aggregateId;

    protected function setUp(): void
    {
        $this->aggregateId = V4AggregateId::create();
    }

    public function testInstance(): void
    {
        $queryFilter = new MatchAggregateGreaterThanVersion($this->aggregateId, AggregateRootStub::class, 1);
        $this->assertEquals('asc', $queryFilter->orderBy());

        $events = $this->provideEvents();

        $eventsFiltered = array_filter($events, $queryFilter->apply());

        $this->assertCount(2, $eventsFiltered);
        $this->assertSame(2, $eventsFiltered[1]->header(EventHeader::AGGREGATE_VERSION));
        $this->assertSame(3, $eventsFiltered[3]->header(EventHeader::AGGREGATE_VERSION));
    }

    public function testInstanceWithAnotherAggregateId(): void
    {
        $aggregateId = V4AggregateId::fromString('89a1b3bd-c811-4a7a-8308-fab6505080b1');
        $queryFilter = new MatchAggregateGreaterThanVersion($aggregateId, AnotherAggregateRootStub::class, 1);

        $events = $this->provideEvents();

        $eventsFiltered = array_filter($events, $queryFilter->apply());

        $this->assertCount(1, $eventsFiltered);
        $this->assertSame(2, $eventsFiltered[4]->header(EventHeader::AGGREGATE_VERSION));
    }

    public function testInstanceWithAnotherAggregateIdReturnEmptyStreamEvents(): void
    {
        $aggregateId = V4AggregateId::fromString('89a1b3bd-c811-4a7a-8308-fab6505080b1');
        $queryFilter = new MatchAggregateGreaterThanVersion($aggregateId, AnotherAggregateRootStub::class, 2);

        $events = $this->provideEvents();

        $eventsFiltered = array_filter($events, $queryFilter->apply());

        $this->assertEmpty($eventsFiltered);
    }

    private function provideEvents(): array
    {
        return [
            SomeEvent::fromContent(['foo' => 'bar'])
                ->withHeader(EventHeader::AGGREGATE_ID, $this->aggregateId)
                ->withHeader(EventHeader::AGGREGATE_ID_TYPE, $this->aggregateId::class)
                ->withHeader(EventHeader::AGGREGATE_TYPE, AggregateRootStub::class)
                ->withHeader(EventHeader::AGGREGATE_VERSION, 1),

            SomeEvent::fromContent(['foo' => 'baz'])
                ->withHeader(EventHeader::AGGREGATE_ID, $this->aggregateId)
                ->withHeader(EventHeader::AGGREGATE_ID_TYPE, $this->aggregateId::class)
                ->withHeader(EventHeader::AGGREGATE_TYPE, AggregateRootStub::class)
                ->withHeader(EventHeader::AGGREGATE_VERSION, 2),

            SomeEvent::fromContent(['foo' => 'baz'])
                ->withHeader(EventHeader::AGGREGATE_ID, '89a1b3bd-c811-4a7a-8308-fab6505080b1')
                ->withHeader(EventHeader::AGGREGATE_ID_TYPE, $this->aggregateId::class)
                ->withHeader(EventHeader::AGGREGATE_TYPE, AnotherAggregateRootStub::class)
                ->withHeader(EventHeader::AGGREGATE_VERSION, 1),

            SomeEvent::fromContent(['foo' => 'foo_bar'])
                ->withHeader(EventHeader::AGGREGATE_ID, $this->aggregateId->toString())
                ->withHeader(EventHeader::AGGREGATE_ID_TYPE, $this->aggregateId::class)
                ->withHeader(EventHeader::AGGREGATE_TYPE, AggregateRootStub::class)
                ->withHeader(EventHeader::AGGREGATE_VERSION, 3),

            SomeEvent::fromContent(['foo' => 'baz'])
                ->withHeader(EventHeader::AGGREGATE_ID, '89a1b3bd-c811-4a7a-8308-fab6505080b1')
                ->withHeader(EventHeader::AGGREGATE_ID_TYPE, $this->aggregateId::class)
                ->withHeader(EventHeader::AGGREGATE_TYPE, AnotherAggregateRootStub::class)
                ->withHeader(EventHeader::AGGREGATE_VERSION, 2),
        ];
    }
}
