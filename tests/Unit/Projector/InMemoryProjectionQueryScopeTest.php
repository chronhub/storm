<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Generator;
use Illuminate\Support\Collection;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Tests\Double\SomeEvent;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Projector\InMemoryProjectionQueryScope;
use Chronhub\Storm\Contracts\Chronicler\InMemoryQueryFilter;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use function count;
use function array_filter;
use function iterator_to_array;

final class InMemoryProjectionQueryScopeTest extends UnitTestCase
{
    /**
     * @test
     *
     * @dataProvider provideCurrentPosition
     */
    public function it_filter_domain_event_from_included_position(int $fromIncludedPosition, int $expectedCount): void
    {
        $queryScope = new InMemoryProjectionQueryScope();

        $events = new Collection(iterator_to_array($this->provideDomainEvents()));
        $this->assertCount(10, $events);

        /** @var InMemoryQueryFilter|ProjectionQueryFilter $queryFilter */
        $queryFilter = $queryScope->fromIncludedPosition();
        $queryFilter->setCurrentPosition($fromIncludedPosition);

        $filteredEvents = $events
            ->sortBy(static fn(DomainEvent $event): int => $event->header(EventHeader::INTERNAL_POSITION), SORT_NUMERIC, 'desc' === $queryFilter->orderBy())
            ->filter($queryFilter->apply());

        $this->assertEquals(count($filteredEvents), $expectedCount);
    }

    /**
     * @test
     *
     * @dataProvider provideInvalidStreamPosition
     */
    public function it_raise_exception_if_current_set_position_is_less_or_equal_than_zero(int $invalidPosition): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Position must be greater than 0, current is $invalidPosition");

        $queryScope = new InMemoryProjectionQueryScope();

        $queryFilter = $queryScope->fromIncludedPosition();

        $queryFilter->setCurrentPosition($invalidPosition);

        $queryFilter->apply()();
    }

    /**
     * @test
     *
     * @dataProvider provideInvalidInternalPositionHeader
     */
    public function it_raise_exception_if_internal_position_header_of_event_is_invalid(null|string $invalidInternalPosition): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Internal position header must return an integer, current is $invalidInternalPosition");

        $queryScope = new InMemoryProjectionQueryScope();

        $queryFilter = $queryScope->fromIncludedPosition();
        $queryFilter->setCurrentPosition(1);

        array_filter(
            [SomeEvent::fromContent([])->withHeader(EventHeader::INTERNAL_POSITION, $invalidInternalPosition)],
            $queryFilter->apply()
        );
    }

    private function provideDomainEvents(): Generator
    {
        $i = 1;

        while ($i !== 11) {
            yield SomeEvent::fromContent([])->withHeader(EventHeader::INTERNAL_POSITION, $i);
            $i++;
        }

        return $i;
    }

    public function provideCurrentPosition(): Generator
    {
        $i = 1;

        while ($i !== 11) {
            yield[$i, 11 - $i];
            $i++;
        }

        return $i;
    }

    public function provideInvalidStreamPosition(): Generator
    {
        yield [0];
        yield [-1];
    }

    public function provideInvalidInternalPositionHeader(): Generator
    {
        yield [null];
        yield ['nope'];
    }
}
