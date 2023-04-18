<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Iterator;

use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Projector\Iterator\StreamEventIterator;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(StreamEventIterator::class)]
final class StreamEventIteratorTest extends UnitTestCase
{
    public function testEmptyGenerator(): void
    {
        $streamEvents = $this->provideEmptyGenerator();

        $iterator = new StreamEventIterator($streamEvents);

        $this->assertNull($iterator->current());
        $this->assertFalse($iterator->key());
        $this->assertFalse($iterator->valid());
    }

    public function testIteratorNextOnInstantiation(): void
    {
        $streamEvents = $this->provideStreamEvens();

        $iterator = new StreamEventIterator($streamEvents);

        $this->assertInstanceOf(SomeEvent::class, $iterator->current());
        $this->assertSame(1, $iterator->key());
        $this->assertTrue($iterator->valid());

        $lastEvent = null;
        while ($iterator->valid()) {
            $iterator->next();

            if ($iterator->current() instanceof DomainEvent) {
                $lastEvent = $iterator->current();
            }
        }

        $this->assertSame(10, $lastEvent->header(EventHeader::INTERNAL_POSITION));
    }

    public function testDoesNotRewind(): void
    {
        $streamEvents = $this->provideStreamEvens();

        $iterator = new StreamEventIterator($streamEvents);

        $this->assertSame(1, $iterator->current()->header(EventHeader::INTERNAL_POSITION));

        $iterator->next();
        $iterator->next();
        $iterator->next();
        $iterator->rewind();

        // generator raise exception normally
        $this->assertSame(4, $iterator->current()->header(EventHeader::INTERNAL_POSITION));
    }

    private function provideStreamEvens(): Generator
    {
        $count = 1;
        while ($count !== 11) {
            $headers = [EventHeader::INTERNAL_POSITION => $count];

            yield SomeEvent::fromContent([])->withHeaders($headers);

            $count++;
        }
    }

    private function provideEmptyGenerator(): Generator
    {
        yield from [];

        return 0;
    }
}
