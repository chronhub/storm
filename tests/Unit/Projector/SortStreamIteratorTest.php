<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Generator;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Tests\Double\SomeEvent;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Projector\Iterator\SortStreamIterator;
use Chronhub\Storm\Projector\Iterator\StreamEventIterator;
use function array_keys;
use function array_values;

final class SortStreamIteratorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_assert_instance(): void
    {
        $streams = $this->provideStreams();

        $iterator = new SortStreamIterator(array_keys($streams), ...array_values($streams));

        $this->assertEquals(3, $iterator->numberOfIterators);

        $this->assertEquals('stream_1', $iterator->originalIteratorOrder[0][1]);
        $this->assertEquals('stream_2', $iterator->originalIteratorOrder[1][1]);
        $this->assertEquals('stream_3', $iterator->originalIteratorOrder[2][1]);
    }

    /**
     * @test
     */
    public function it_assert_valid(): void
    {
        $streams = $this->provideStreams();

        $iterator = new SortStreamIterator(array_keys($streams), ...array_values($streams));

        $this->assertTrue($iterator->valid());
    }

    /**
     * @test
     */
    public function it_prioritize_stream_by_event_time(): void
    {
        $streams = $this->provideStreams();

        $iterator = new SortStreamIterator(array_keys($streams), ...array_values($streams));

        $count = 0;
        foreach ($iterator as $position => $event) {
            $count++;

            if ($count === 1) {
                $this->assertEquals('stream_3', $iterator->streamName());
                $this->assertEquals('2019-05-10T10:18:19.388500', $event->header(Header::EVENT_TIME));
            }

            if ($count === 9) {
                $this->assertEquals('stream_1', $iterator->streamName());
                $this->assertEquals('2019-05-10T10:18:19.388520', $event->header(Header::EVENT_TIME));
            }
        }
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
                ->withHeader(Header::EVENT_TIME, '2019-05-10T10:18:19.388500'),

            SomeEvent::fromContent([])
                ->withHeader(EventHeader::INTERNAL_POSITION, 3)
                ->withHeader(Header::EVENT_TIME, '2019-05-10T10:18:19.388503'),
        ];

        foreach ($events as $event) {
            yield $event;
        }

        return 2;
    }
}
