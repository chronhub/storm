<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Snapshot;

use Chronhub\Storm\Aggregate\V4AggregateId;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Snapshot\MatchAggregateBetweenIncludedVersion;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

use function array_filter;

#[CoversClass(MatchAggregateBetweenIncludedVersion::class)]
final class MatchAggregateBetweenIncludedVersionTest extends UnitTestCase
{
    private V4AggregateId $aggregateId;

    protected function setUp(): void
    {
        $this->aggregateId = V4AggregateId::create();
    }

    public function testInstance(): void
    {
        $queryFilter = new MatchAggregateBetweenIncludedVersion($this->aggregateId, 1, 3);
        $this->assertSame('asc', $queryFilter->orderBy());

        $events = $this->provideEvents();

        $eventsFiltered = array_filter($events, $queryFilter->apply());

        $this->assertCount(3, $eventsFiltered);
        $this->assertSame(1, $eventsFiltered[0]->header(EventHeader::AGGREGATE_VERSION));
        $this->assertSame(2, $eventsFiltered[1]->header(EventHeader::AGGREGATE_VERSION));
        $this->assertSame(3, $eventsFiltered[3]->header(EventHeader::AGGREGATE_VERSION));
    }

    public function testInstanceWithAnotherAggregateId(): void
    {
        $aggregateId = V4AggregateId::fromString('89a1b3bd-c811-4a7a-8308-fab6505080b1');
        $queryFilter = new MatchAggregateBetweenIncludedVersion($aggregateId, 1, PHP_INT_MAX);

        $events = $this->provideEvents();

        $eventsFiltered = array_filter($events, $queryFilter->apply());

        $this->assertCount(1, $eventsFiltered);
        $this->assertSame(1, $eventsFiltered[2]->header(EventHeader::AGGREGATE_VERSION));
    }

    private function provideEvents(): array
    {
        return [
            SomeEvent::fromContent(['foo' => 'bar'])
                ->withHeader(EventHeader::AGGREGATE_ID, $this->aggregateId)
                ->withHeader(EventHeader::AGGREGATE_ID_TYPE, $this->aggregateId::class)
                ->withHeader(EventHeader::AGGREGATE_VERSION, 1),

            SomeEvent::fromContent(['foo' => 'baz'])
                ->withHeader(EventHeader::AGGREGATE_ID, $this->aggregateId)
                ->withHeader(EventHeader::AGGREGATE_ID_TYPE, $this->aggregateId::class)
                ->withHeader(EventHeader::AGGREGATE_VERSION, 2),

            SomeEvent::fromContent(['foo' => 'baz'])
                ->withHeader(EventHeader::AGGREGATE_ID, '89a1b3bd-c811-4a7a-8308-fab6505080b1')
                ->withHeader(EventHeader::AGGREGATE_ID_TYPE, $this->aggregateId::class)
                ->withHeader(EventHeader::AGGREGATE_VERSION, 1),

            SomeEvent::fromContent(['foo' => 'foo_bar'])
                ->withHeader(EventHeader::AGGREGATE_ID, $this->aggregateId->toString())
                ->withHeader(EventHeader::AGGREGATE_ID_TYPE, $this->aggregateId::class)
                ->withHeader(EventHeader::AGGREGATE_VERSION, 3),
        ];
    }
}
