<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Chronhub\Storm\Contracts\Chronicler\InMemoryQueryFilter;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Projector\InMemoryQueryScope;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use function array_filter;
use function count;
use function iterator_to_array;

#[CoversClass(InMemoryQueryScope::class)]
final class InMemoryQueryScopeTest extends UnitTestCase
{
    #[DataProvider('provideCurrentPosition')]
    public function testFilterStreamEventFromCurrentPosition(int $fromIncludedPosition, int $expectedCount): void
    {
        $queryScope = new InMemoryQueryScope();

        $events = new Collection(iterator_to_array($this->provideDomainEvents()));
        $this->assertCount(10, $events);

        /** @var InMemoryQueryFilter|ProjectionQueryFilter $queryFilter */
        $queryFilter = $queryScope->fromIncludedPosition();
        $queryFilter->setCurrentPosition($fromIncludedPosition);

        $filteredEvents = $events
            ->sortBy(
                static fn (DomainEvent $event): int => $event->header(EventHeader::INTERNAL_POSITION), SORT_NUMERIC, 'desc' === $queryFilter->orderBy()
            )
            ->filter($queryFilter->apply());

        $this->assertEquals(count($filteredEvents), $expectedCount);
    }

    #[DataProvider('provideInvalidStreamPosition')]
    public function testExceptionRaisedWhenPositionIsLessThanOne(int $invalidPosition): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Position must be greater than 0, current is $invalidPosition");

        $queryScope = new InMemoryQueryScope();

        $queryFilter = $queryScope->fromIncludedPosition();

        $queryFilter->setCurrentPosition($invalidPosition);

        $queryFilter->apply()();
    }

    #[DataProvider('provideInvalidInternalPositionHeader')]
    public function testExceptionRaisedWithInvalidInternalPositionHeader(null|string $invalidInternalPosition): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Internal position header must return an integer, current is $invalidInternalPosition");

        $queryScope = new InMemoryQueryScope();

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

    public static function provideCurrentPosition(): Generator
    {
        $i = 1;

        while ($i !== 11) {
            yield [$i, 11 - $i];
            $i++;
        }

        return $i;
    }

    public static function provideInvalidStreamPosition(): Generator
    {
        yield [0];
        yield [-1];
    }

    public static function provideInvalidInternalPositionHeader(): Generator
    {
        yield [null];
        yield ['nope'];
    }
}
