<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Activity;

use Chronhub\Storm\Chronicler\Exceptions\NoStreamEventReturn;
use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Clock\PointInTimeFactory;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\LoadLimiterProjectionQueryFilter;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Contracts\Projector\StreamNameAwareQueryFilter;
use Chronhub\Storm\Projector\Activity\LoadStreamsToRemove;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Tests\Factory\StreamEventsFactory;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Generator;

// todo refactor test to activity load tests

beforeEach(function () {
    $this->chronicler = $this->createMock(Chronicler::class);
    $this->clock = $this->createMock(SystemClock::class);

    $this->loadStreams = new LoadStreamsToRemove($this->chronicler, $this->clock);
});

dataset('streamEvents', [
    fn () => yield from StreamEventsFactory::fromArray([
        StreamEventsFactory::withEvent(SomeEvent::class)->withHeaders(PointInTimeFactory::now(), 11),
        StreamEventsFactory::withEvent(SomeEvent::class)->withHeaders(PointInTimeFactory::now(), 12),
    ]),
]);

dataset('streamEvent2', [
    fn () => yield from StreamEventsFactory::fromArray([
        StreamEventsFactory::withEvent(SomeEvent::class)->withHeaders(PointInTimeFactory::now(), 22),
        StreamEventsFactory::withEvent(SomeEvent::class)->withHeaders(PointInTimeFactory::now(), 24),
    ]),
]);

describe('can load streams from', function () {

    test('query filter', function (Generator $events) {
        $queryFilter = $this->createMock(QueryFilter::class);

        $this->chronicler->expects($this->exactly(1))->method('retrieveFiltered')->willReturn($events);
        $this->clock->expects($this->never())->method('toPointInTime');

        $this->loadStreams->batch(['customer-123' => 4], $queryFilter, 10);
    })->with('streamEvents');

    test('projection query filter', function (Generator $events, Generator $events2) {
        $queryFilter = $this->createMock(ProjectionQueryFilter::class);

        $matcher = $this->exactly(2);
        $queryFilter->expects($matcher)->method('setCurrentPosition')
            ->willReturnCallback(function (int $position) use ($matcher) {
                match ($matcher->numberOfInvocations()) {
                    1 => expect($position)->toBe(5),
                    2 => expect($position)->toBe(9),
                    default => null,
                };
            });

        $this->chronicler->expects($this->exactly(2))->method('retrieveFiltered')->willReturnOnConsecutiveCalls($events, $events2);
        $this->clock->expects($this->exactly(2))->method('toPointInTime')->willReturn(PointInTimeFactory::now());

        $this->loadStreams->batch(['customer-123' => 4, 'customer-456' => 8], $queryFilter, 10);
    })->with('streamEvents', 'streamEvent2');

    test('stream name aware projection query filter', function (Generator $events, Generator $events2) {
        $queryFilter = $this->createMock(StreamNameAwareQueryFilter::class);

        // todo missing expectation from setCurrentPosition

        $matcher = $this->exactly(2);
        $queryFilter->expects($matcher)->method('setCurrentStreamName')
            ->willReturnCallback(function (string $streamName) use ($matcher) {
                match ($matcher->numberOfInvocations()) {
                    1 => expect($streamName)->toBe('customer-123'),
                    2 => expect($streamName)->toBe('customer-456'),
                    default => null,
                };
            });

        $this->chronicler->expects($this->exactly(2))->method('retrieveFiltered')->willReturnOnConsecutiveCalls($events, $events2);
        $this->clock->expects($this->exactly(2))->method('toPointInTime')->willReturn(PointInTimeFactory::now());

        $this->loadStreams->batch(['customer-123' => 4, 'customer-456' => 8], $queryFilter, 10);
    })->with('streamEvents', 'streamEvent2');

    test('load limiter per stream', function (?int $loadLimiter) {
        $queryFilter = $this->createMock(LoadLimiterProjectionQueryFilter::class);

        if ($loadLimiter === null) {
            $queryFilter->expects($this->never())->method('setLimit');
        } else {
            $queryFilter->expects($this->exactly(2))->method('setLimit')->with($loadLimiter);
        }

        $this->chronicler->expects($this->exactly(2))->method('retrieveFiltered');

        $this->loadStreams->batch(['customer-123' => 4, 'customer-456' => 8], $queryFilter, $loadLimiter);
    })->with([
        'null' => [null],
        'positive int' => [1, 5, 100],
    ]);
});

test('catch stream not found exception', function (StreamNotFound $exception) {
    $queryFilter = $this->createMock(QueryFilter::class);

    $this->chronicler->expects($this->exactly(1))->method('retrieveFiltered')->willThrowException($exception);

    $streams = $this->loadStreams->batch(['customer-123' => 4], $queryFilter, 10);

    expect($streams)->toBeInstanceOf(MergeStreamIterator::class)
        ->and($streams->valid())->toBeFalse()
        ->and($streams->count())->toBe(0);
})->with([
    'stream not found' => [new StreamNotFound('stream not found')],
    'no stream event return' => [new NoStreamEventReturn('no event return')],
]);

test('continue iteration with next stream on stream not found exception raised', function (Generator $events) {
    $queryFilter = $this->createMock(QueryFilter::class);

    $this->chronicler->expects($this->exactly(2))->method('retrieveFiltered')
        ->willReturnOnConsecutiveCalls(
            StreamEventsFactory::fromEmptyAndRaiseStreamNotFoundException('customer-123'),
            $events
        );

    $streams = $this->loadStreams->batch(['customer-123' => 4, 'customer-456' => 8], $queryFilter, 10);

    expect($streams)->toBeInstanceOf(MergeStreamIterator::class)
        ->and($streams->valid())->toBeTrue()
        ->and($streams->numberOfEvents)->toBe(2);
})->with('streamEvents');
