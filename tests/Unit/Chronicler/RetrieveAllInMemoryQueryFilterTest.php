<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Chronicler;

use Chronhub\Storm\Aggregate\V4AggregateId;
use Chronhub\Storm\Chronicler\InMemory\RetrieveAllInMemoryQueryFilter;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Chronicler\InMemoryQueryFilter;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use function array_filter;
use function array_map;
use function array_values;

class RetrieveAllInMemoryQueryFilterTest extends UnitTestCase
{
    public function testInstance(): void
    {
        $filter = new RetrieveAllInMemoryQueryFilter(
            $this->createMock(AggregateIdentity::class),
            'asc'
        );

        $this->assertInstanceOf(InMemoryQueryFilter::class, $filter);
    }

    public function testFilterEventsPerAggregateId(): void
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

    #[DataProvider('provideDirection')]
    public function testGetSorting(string $sorting): void
    {
        $filter = new RetrieveAllInMemoryQueryFilter(
            $this->createMock(AggregateIdentity::class),
            $sorting
        );

        $this->assertEquals($sorting, $filter->orderBy());
    }

    public static function provideDirection(): Generator
    {
        yield ['asc'];
        yield ['desc'];
    }
}
