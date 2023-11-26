<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Iterator;

use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Projector\Iterator\StreamIterator;
use Chronhub\Storm\Tests\Factory\MergeStreamIteratorFactory;
use Chronhub\Storm\Tests\Factory\StreamEventsFactory;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;

beforeEach(function (): void {
    $this->clock = new PointInTime();
    $this->streams = MergeStreamIteratorFactory::getIterator($this->clock);
});

describe('raised stream not found exception in constructor', function (): void {
    test('from empty stream events iterator', function (): void {
        new MergeStreamIterator($this->clock, ['nope'], new StreamIterator(StreamEventsFactory::fromEmpty()));
    })->throws(StreamNotFound::class, 'Stream not found');

    test('send by generator', function (): void {
        new MergeStreamIterator($this->clock, [], new StreamIterator(StreamEventsFactory::fromEmptyAndRaiseStreamNotFoundException('foo')));
    })->throws(StreamNotFound::class, 'Stream foo not found');
});

test('iterator pointer advance in constructor', function (): void {
    expect($this->streams->streamName())->toBe('stream2')
        ->and($this->streams->key())->toBe(5)
        ->and($this->streams->current())->toBeInstanceOf(SomeEvent::class)
        ->and($this->streams->current()->header(Header::EVENT_TIME))->toBe('2023-05-10T10:15:19.000000');
});

test('iterate by ordered event time across streams', function () {
    $streamsOrder = [];

    $previousEventTime = null;

    while ($this->streams->valid()) {
        $streamsOrder[] = $this->streams->streamName();

        $eventTime = $this->clock->toPointInTime($this->streams->current()->header(Header::EVENT_TIME));

        $this->assertTrue($previousEventTime === null || $eventTime > $previousEventTime);

        $previousEventTime = $eventTime;

        $this->streams->next();
    }

    expect($streamsOrder)->toBe(MergeStreamIteratorFactory::expectedIteratorOrder());
});

test('iterate over key as event position', function (): void {
    $eventPositions = [];
    while ($this->streams->valid()) {
        $eventPositions[] = $this->streams->key();

        $this->streams->next();
    }

    expect($eventPositions)->toBe(MergeStreamIteratorFactory::expectedIteratorPosition());
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

test('failed rewind when all streams are not valid', function () {
    while ($this->streams->valid()) {
        $this->streams->next();
    }

    expect($this->streams->valid())->toBeFalse();

    $this->streams->rewind();

    expect($this->streams->valid())->toBeFalse();
});

test('count total of events across streams', function (): void {
    expect($this->streams->count())->toBe(9);
});
