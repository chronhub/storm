<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Chronicler;

use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Tests\Double\SomeEvent;
use Chronhub\Storm\Aggregate\V4AggregateId;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Chronicler\InMemoryQueryFilter;
use Chronhub\Storm\Chronicler\InMemory\RetrieveAllInMemoryQueryFilter;
use function array_map;
use function array_filter;
use function array_values;

class RetrieveAllInMemoryQueryFilterTest extends UnitTestCase
{
    #[Test]
    public function it_can_be_instantiated(): void
    {
        $filter = new RetrieveAllInMemoryQueryFilter(
            $this->createMock(AggregateIdentity::class),
            'asc'
        );

        $this->assertInstanceOf(InMemoryQueryFilter::class, $filter);
    }

    #[Test]
    public function it_filter_events_per_aggregate_id(): void
    {
        $expectedAggregateId = V4AggregateId::create();

        $events = [
            SomeEvent::fromContent(['count' => 1])->withHeader(EventHeader::AGGREGATE_ID, $expectedAggregateId->toString()),
            SomeEvent::fromContent(['count' => 2])->withHeader(EventHeader::AGGREGATE_ID, $expectedAggregateId),
            SomeEvent::fromContent(['another_count' => 1])->withHeader(EventHeader::AGGREGATE_ID, V4AggregateId::create()->toString()),
            SomeEvent::fromContent(['count' => 3])->withHeader(EventHeader::AGGREGATE_ID, $expectedAggregateId->toString()),
        ];

        $filter = new RetrieveAllInMemoryQueryFilter($expectedAggregateId, 'asc');

        $filteredEvents = array_filter($events, $filter->apply());

        $this->assertCount(3, $filteredEvents);

        $this->assertEquals([
            ['count' => 1],
            ['count' => 2],
            ['count' => 3],
        ], array_values(array_map(fn (SomeEvent $event) => $event->content, $filteredEvents)));
    }

    #[Test]
    public function it_access_sort_ascendant_direction(): void
    {
        $filter = new RetrieveAllInMemoryQueryFilter(
            $this->createMock(AggregateIdentity::class),
            'asc'
        );

        $this->assertEquals('asc', $filter->orderBy());
    }

    #[Test]
    public function it_access_sort_descendant_direction(): void
    {
        $filter = new RetrieveAllInMemoryQueryFilter(
            $this->createMock(AggregateIdentity::class),
            'desc'
        );

        $this->assertEquals('desc', $filter->orderBy());
    }
}