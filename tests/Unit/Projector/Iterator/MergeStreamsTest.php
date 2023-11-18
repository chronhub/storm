<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Iterator;

use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Projector\Iterator\StreamIterator;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use DateTimeImmutable;
use Generator;

use function array_keys;
use function array_shift;
use function array_values;

class MergeStreamsTest extends UnitTestCase
{
    public function testAdvancePointerOnConstructor(): void
    {
        $streams = $this->getStreams();

        $mergeStreams = new MergeStreamIterator(array_keys($streams), ...array_values($streams));

        $this->assertEquals('stream3', $mergeStreams->streamName());
    }

    public function testMergeStreams(): void
    {
        // note: all provided events must have already sorted by event time

        $streams = $this->getStreams();

        $expectedStreamsOrder = [
            'stream3', 'stream1', 'stream2',
            'stream1', 'stream3', 'stream2',
            'stream2', 'stream1', 'stream3',
        ];

        $mergeStreams = new MergeStreamIterator(array_keys($streams), ...array_values($streams));

        $previousEventTime = null;

        while ($mergeStreams->valid()) {
            $expectedStreamName = array_shift($expectedStreamsOrder);

            $this->assertEquals($expectedStreamName, $mergeStreams->streamName());

            $eventTime = DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s.u', $mergeStreams->current()->header(Header::EVENT_TIME));

            $this->assertTrue($previousEventTime === null || $eventTime > $previousEventTime);

            $previousEventTime = $eventTime;

            $mergeStreams->next();
        }
    }

    /**
     * @test
     */
    public function testIterateOverKeyAsEventPosition(): void
    {
        $streams = $this->getStreams();

        $mergeStreams = new MergeStreamIterator(array_keys($streams), ...array_values($streams));

        $expectedPosition = [3, 1, 5, 4, 8, 7, 2, 6, 9];

        while ($mergeStreams->valid()) {
            $this->assertEquals(array_shift($expectedPosition), $mergeStreams->key());

            $mergeStreams->next();
        }
    }

    /**
     * @test
     */
    public function testCountTotalOfEvents(): void
    {
        $streams = $this->getStreams();

        $mergeStreams = new MergeStreamIterator(array_keys($streams), ...array_values($streams));

        $this->assertSame(9, $mergeStreams->count());
    }

    /**
     * @test
     */
    public function testCanRewind(): void
    {
        $streams = $this->getStreams();
        $mergeStreams = new MergeStreamIterator(array_keys($streams), ...array_values($streams));

        $this->assertSame('stream3', $mergeStreams->streamName());

        $mergeStreams->next();

        $this->assertSame('stream1', $mergeStreams->streamName());

        $mergeStreams->next();

        $this->assertSame('stream2', $mergeStreams->streamName());

        $mergeStreams->rewind();

        $this->assertSame('stream3', $mergeStreams->streamName());
    }

    private function provideEventsForStream1(): Generator
    {
        yield SomeEvent::fromContent([])->withHeaders([EventHeader::INTERNAL_POSITION => 1, Header::EVENT_TIME => '2023-05-10T10:15:19.000']);
        yield SomeEvent::fromContent([])->withHeaders([EventHeader::INTERNAL_POSITION => 4, Header::EVENT_TIME => '2023-05-10T10:17:19.000']);
        yield SomeEvent::fromContent([])->withHeaders([EventHeader::INTERNAL_POSITION => 6, Header::EVENT_TIME => '2023-05-10T10:24:19.000']);

        return 3;
    }

    private function provideEventsForStream2(): Generator
    {
        yield SomeEvent::fromContent([])->withHeaders([EventHeader::INTERNAL_POSITION => 5, Header::EVENT_TIME => '2023-05-10T10:16:19.000']);
        yield SomeEvent::fromContent([])->withHeaders([EventHeader::INTERNAL_POSITION => 7, Header::EVENT_TIME => '2023-05-10T10:20:19.000']);
        yield SomeEvent::fromContent([])->withHeaders([EventHeader::INTERNAL_POSITION => 2, Header::EVENT_TIME => '2023-05-10T10:22:19.000']);

        return 3;
    }

    private function provideEventsForStream3(): Generator
    {
        yield SomeEvent::fromContent([])->withHeaders([EventHeader::INTERNAL_POSITION => 3, Header::EVENT_TIME => '2023-05-10T10:14:19.000']);
        yield SomeEvent::fromContent([])->withHeaders([EventHeader::INTERNAL_POSITION => 8, Header::EVENT_TIME => '2023-05-10T10:19:19.000']);
        yield SomeEvent::fromContent([])->withHeaders([EventHeader::INTERNAL_POSITION => 9, Header::EVENT_TIME => '2023-05-10T10:26:19.000']);

        return 3;
    }

    /**
     * @return array<string, StreamIterator>
     */
    private function getStreams(): array
    {
        return [
            'stream2' => new StreamIterator($this->provideEventsForStream2()),
            'stream1' => new StreamIterator($this->provideEventsForStream1()),
            'stream3' => new StreamIterator($this->provideEventsForStream3()),
        ];
    }
}
