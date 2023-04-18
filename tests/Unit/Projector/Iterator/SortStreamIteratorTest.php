<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Iterator;

use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Projector\Iterator\SortStreamIterator;
use Chronhub\Storm\Projector\Iterator\StreamEventIterator;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use DateTimeImmutable;
use DateTimeZone;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use function array_keys;
use function array_values;

#[CoversClass(SortStreamIterator::class)]
final class SortStreamIteratorTest extends UnitTestCase
{
    public function testInstance(): void
    {
        $streams = $this->provideStreams();

        $iterator = new SortStreamIterator(array_keys($streams), ...array_values($streams));

        $this->assertSame(3, $iterator->numberOfIterators);

        $this->assertSame('stream_1', $iterator->originalIteratorOrder[0][1]);
        $this->assertSame('stream_2', $iterator->originalIteratorOrder[1][1]);
        $this->assertSame('stream_3', $iterator->originalIteratorOrder[2][1]);
    }

    public function testAssertValid(): void
    {
        $streams = $this->provideStreams();

        $iterator = new SortStreamIterator(array_keys($streams), ...array_values($streams));

        $this->assertTrue($iterator->valid());
    }

    public function testPrioritizeEventByComparingEventTime(): void
    {
        $streams = $this->provideStreams();

        $iterator = new SortStreamIterator(array_keys($streams), ...array_values($streams));

        $count = 0;
        foreach ($iterator as $event) {
            $count++;

            if ($count === 1) {
                $this->assertSame('stream_3', $iterator->streamName());
                $this->assertEquals(
                    new DateTimeImmutable('2019-05-10T10:18:19.388500', new DateTimeZone('UTC')),
                    $event->header(Header::EVENT_TIME));
            }

            if ($count === 9) {
                $this->assertSame('stream_1', $iterator->streamName());
                $this->assertSame('2019-05-10T10:18:19.388520', $event->header(Header::EVENT_TIME));
            }
        }
    }

    public function testDoesNotRewind(): void
    {
        $streams = $this->provideStreams();

        $iterator = new SortStreamIterator(array_keys($streams), ...array_values($streams));
        $iterator->next();
        $iterator->next();

        $iteratorCp = $iterator;
        $iterator->rewind();

        $this->assertSame($iterator, $iteratorCp);
    }

    public function provideStreams(): array
    {
        return [
            'stream_1' => new StreamEventIterator($this->provideEventsForStream1()),
            'stream_2' => new StreamEventIterator($this->provideEventsForStream2()),
            'stream_3' => new StreamEventIterator($this->provideEventsForStream3()),
        ];
    }

    private function provideEventsForStream1(): Generator
    {
        $events = [
            SomeEvent::fromContent([])
                ->withHeader(EventHeader::INTERNAL_POSITION, 5)
                ->withHeader(Header::EVENT_TIME, '2019-05-10T10:18:19.388510'),

            SomeEvent::fromContent([])
                ->withHeader(EventHeader::INTERNAL_POSITION, 7)
                ->withHeader(Header::EVENT_TIME, '2019-05-10T10:18:19.388519'),

            SomeEvent::fromContent([])
                ->withHeader(EventHeader::INTERNAL_POSITION, 8)
                ->withHeader(Header::EVENT_TIME, '2019-05-10T10:18:19.388520'),
        ];

        foreach ($events as $event) {
            yield $event;
        }

        return 3;
    }

    private function provideEventsForStream2(): Generator
    {
        $events = [
            SomeEvent::fromContent([])
                ->withHeader(EventHeader::INTERNAL_POSITION, 1)
                ->withHeader(Header::EVENT_TIME, '2019-05-10T10:18:19.388501'),

            SomeEvent::fromContent([])
                ->withHeader(EventHeader::INTERNAL_POSITION, 2)
                ->withHeader(Header::EVENT_TIME, '2019-05-10T10:18:19.388503'),

            SomeEvent::fromContent([])
                ->withHeader(EventHeader::INTERNAL_POSITION, 4)
                ->withHeader(Header::EVENT_TIME, '2019-05-10T10:18:19.388509'),

            SomeEvent::fromContent([])
                ->withHeader(EventHeader::INTERNAL_POSITION, 6)
                ->withHeader(Header::EVENT_TIME, '2019-05-10T10:18:19.388515'),
        ];

        foreach ($events as $event) {
            yield $event;
        }

        return 4;
    }

    private function provideEventsForStream3(): Generator
    {
        $events = [
            SomeEvent::fromContent([])
                ->withHeader(EventHeader::INTERNAL_POSITION, 1)
                ->withHeader(
                    Header::EVENT_TIME,
                    new DateTimeImmutable('2019-05-10T10:18:19.388500', new DateTimeZone('UTC'))
                ),

            SomeEvent::fromContent([])
                ->withHeader(EventHeader::INTERNAL_POSITION, 3)
                ->withHeader(
                    Header::EVENT_TIME,
                    new DateTimeImmutable('2019-05-10T10:18:19.388503', new DateTimeZone('UTC'))
                ),
        ];

        foreach ($events as $event) {
            yield $event;
        }

        return 2;
    }
}
