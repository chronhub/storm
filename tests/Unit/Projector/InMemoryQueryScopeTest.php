<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Chronhub\Storm\Contracts\Chronicler\InMemoryQueryFilter;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Projector\InMemoryQueryScope;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
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
}
