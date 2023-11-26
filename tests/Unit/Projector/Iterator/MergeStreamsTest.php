<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Iterator;

use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Projector\Iterator\StreamIterator;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;

use function array_keys;
use function array_values;

beforeEach(function (): void {
    // note that all provided events must have already sorted by event time
    $data = [
        'stream1' => function () {
            yield from [
                SomeEvent::fromContent([])->withHeaders([EventHeader::INTERNAL_POSITION => 1, Header::EVENT_TIME => '2023-05-10T10:16:19.000000']),
                SomeEvent::fromContent([])->withHeaders([EventHeader::INTERNAL_POSITION => 4, Header::EVENT_TIME => '2023-05-10T10:17:19.000000']),
                SomeEvent::fromContent([])->withHeaders([EventHeader::INTERNAL_POSITION => 6, Header::EVENT_TIME => '2023-05-10T10:24:19.000000']),
            ];

            return 3;
        },

        'stream2' => function () {
            yield from [
                SomeEvent::fromContent([])->withHeaders([EventHeader::INTERNAL_POSITION => 5, Header::EVENT_TIME => '2023-05-10T10:15:19.000000']),
                SomeEvent::fromContent([])->withHeaders([EventHeader::INTERNAL_POSITION => 7, Header::EVENT_TIME => '2023-05-10T10:20:19.000000']),
                SomeEvent::fromContent([])->withHeaders([EventHeader::INTERNAL_POSITION => 2, Header::EVENT_TIME => '2023-05-10T10:22:19.000000']),
            ];

            return 3;
        },

        'stream3' => function () {
            yield from [
                SomeEvent::fromContent([])->withHeaders([EventHeader::INTERNAL_POSITION => 3, Header::EVENT_TIME => '2023-05-10T10:18:19.000000']),
                SomeEvent::fromContent([])->withHeaders([EventHeader::INTERNAL_POSITION => 8, Header::EVENT_TIME => '2023-05-10T10:19:19.000000']),
                SomeEvent::fromContent([])->withHeaders([EventHeader::INTERNAL_POSITION => 9, Header::EVENT_TIME => '2023-05-10T10:23:19.000000']),
            ];

            return 3;
        },
    ];

    $streams = [];

    foreach ($data as $streamName => $streamEvents) {
        $streams[$streamName] = new StreamIterator($streamEvents());
    }

    $this->clock = new PointInTime();
    $this->streams = new MergeStreamIterator($this->clock, array_keys($streams), ...array_values($streams));
});

test('pointer advance in constructor', function (): void {
    expect($this->streams->streamName())->toBe('stream2')
        ->and($this->streams->key())->toBe(5)
        ->and($this->streams->current())->toBeInstanceOf(SomeEvent::class)
        ->and($this->streams->current()->header(Header::EVENT_TIME))->toBe('2023-05-10T10:15:19.000000');
});

test('streams are ordered by event time', function () {
    $expectedStreamsOrder = [
        'stream2', 'stream1', 'stream1',
        'stream3', 'stream3', 'stream2',
        'stream2', 'stream3', 'stream1',
    ];

    $streamsOrder = [];

    $previousEventTime = null;

    while ($this->streams->valid()) {
        $streamsOrder[] = $this->streams->streamName();

        $eventTime = $this->clock->toPointInTime($this->streams->current()->header(Header::EVENT_TIME));

        $this->assertTrue($previousEventTime === null || $eventTime > $previousEventTime);

        $previousEventTime = $eventTime;

        $this->streams->next();
    }

    expect($streamsOrder)->toBe($expectedStreamsOrder);
});

test('iterate over key as event position', function (): void {
    $expectedPosition = [5, 1, 4, 3, 8, 7, 2, 9, 6];

    $eventPositions = [];
    while ($this->streams->valid()) {
        $eventPositions[] = $this->streams->key();

        $this->streams->next();
    }

    expect($eventPositions)->toBe($expectedPosition);
});

test('count total of events', function (): void {
    expect($this->streams->count())->toBe(9);
});

test('rewind streams', function (): void {
    expect($this->streams->streamName())->toBe('stream2');

    $this->streams->next();
    $this->streams->next();
    $this->streams->next();

    expect($this->streams->streamName())->toBe('stream3');

    $this->streams->rewind();

    expect($this->streams->streamName())->toBe('stream2');
});
