<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Activity;

use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Clock\PointInTimeFactory;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Projector\CheckpointRecognition;
use Chronhub\Storm\Contracts\Projector\ContextReader;
use Chronhub\Storm\Contracts\Projector\LoadLimiterProjectionQueryFilter;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Contracts\Projector\StateManagement;
use Chronhub\Storm\Contracts\Projector\StreamNameAwareQueryFilter;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Projector\Workflow\Activity\LoadStreams;
use Chronhub\Storm\Tests\Factory\StreamEventsFactory;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Generator;

beforeEach(function (): void {
    $this->subscription = $this->createMock(StateManagement::class);
    $this->streamManager = $this->createMock(CheckpointRecognition::class);
    $this->context = $this->createMock(ContextReader::class);
    $this->option = $this->createMock(ProjectionOption::class);
    $this->chronicler = $this->createMock(Chronicler::class);

    $this->subscription->expects($this->once())->method('streamManager')->willReturn($this->streamManager);
    $this->subscription->method('chronicler')->willReturn($this->chronicler);
    $this->activity = new LoadStreams();
    $this->next = fn (StateManagement $subscription) => fn () => 42;
});

function getLimiterQueryFilter(?int $limit): LoadLimiterProjectionQueryFilter
{
    return new class($limit) implements LoadLimiterProjectionQueryFilter
    {
        public int $streamPosition;

        public function __construct(public ?int $limit)
        {
        }

        public function setLoadLimiter(?int $loadLimiter): void
        {
            $this->limit = $loadLimiter;
        }

        public function setStreamPosition(int $streamPosition): void
        {
            $this->streamPosition = $streamPosition;
        }

        public function apply(): callable
        {
            return fn () => null;
        }
    };
}

function getStreamEvents(): Generator
{
    return StreamEventsFactory::fromArray([
        StreamEventsFactory::withEvent(SomeEvent::class)->withHeaders(PointInTimeFactory::now(), 22),
        StreamEventsFactory::withEvent(SomeEvent::class)->withHeaders(PointInTimeFactory::now(), 24),
    ]);
}

it('does not iterate when streams positions are empty', function () {
    $this->streamManager->expects($this->once())->method('jsonSerialize')->willReturn([]);
    $this->subscription->expects($this->never())->method('setStreamIterator');
    $this->chronicler->expects($this->never())->method('retrieveFiltered');

    $next = ($this->activity)($this->subscription, $this->next);

    expect($next())->toBe(42);
});

it('set merge stream iterator', function () {
    $queryFilter = $this->createMock(QueryFilter::class);

    $streamEvents = StreamEventsFactory::fromArray([
        StreamEventsFactory::withEvent(SomeEvent::class)->withHeaders(PointInTimeFactory::now(), 11),
        StreamEventsFactory::withEvent(SomeEvent::class)->withHeaders(PointInTimeFactory::now(), 12),
    ]);

    $this->option->expects($this->never())->method('getLoadLimiter');
    $this->context->expects($this->once())->method('queryFilter')->willReturn($queryFilter);
    $this->subscription->expects($this->once())->method('context')->willReturn($this->context);
    $this->streamManager->expects($this->once())->method('jsonSerialize')->willReturn(['customer-123' => 1]);
    $this->chronicler->expects($this->once())->method('retrieveFiltered')->willReturn($streamEvents);

    $this->subscription->expects($this->once())->method('setStreamIterator')->with(
        $this->callback(fn ($iterator) => $iterator instanceof MergeStreamIterator)
    );

    $next = ($this->activity)($this->subscription, $this->next);

    expect($next())->toBe(42);
});

it('set current stream name on query filter', function () {
    $queryFilter = $this->createMock(StreamNameAwareQueryFilter::class);
    $queryFilter->expects($this->once())->method('setStreamName')->with('customer-123');

    $streamEvents = getStreamEvents();

    $this->option->expects($this->never())->method('getLoadLimiter');
    $this->context->expects($this->once())->method('queryFilter')->willReturn($queryFilter);
    $this->subscription->expects($this->once())->method('context')->willReturn($this->context);
    $this->streamManager->expects($this->once())->method('jsonSerialize')->willReturn(['customer-123' => 1]);
    $this->chronicler->expects($this->once())->method('retrieveFiltered')->willReturn($streamEvents);

    $this->subscription->expects($this->once())->method('setStreamIterator')->with(
        $this->callback(fn ($iterator) => $iterator instanceof MergeStreamIterator)
    );

    $next = ($this->activity)($this->subscription, $this->next);

    expect($next())->toBe(42);
});

it('set limit on query filter', function (?int $limit) {
    $queryFilter = getLimiterQueryFilter($limit);
    $streamEvents = getStreamEvents();

    $this->chronicler->expects($this->once())->method('retrieveFiltered')->willReturn($streamEvents);

    $this->option->expects($this->once())->method('getLoadLimiter')->willReturn($limit);
    $this->context->expects($this->once())->method('queryFilter')->willReturn($queryFilter);
    $this->subscription->expects($this->once())->method('option')->willReturn($this->option);
    $this->subscription->expects($this->once())->method('context')->willReturn($this->context);
    $this->streamManager->expects($this->once())->method('jsonSerialize')->willReturn(['customer-123' => 1]);

    $this->subscription->expects($this->once())->method('setStreamIterator')->with(
        $this->callback(fn ($iterator) => $iterator instanceof MergeStreamIterator)
    );

    $next = ($this->activity)($this->subscription, $this->next);

    expect($next())->toBe(42)
        ->and($queryFilter->limit)->toBe($limit)
        ->and($queryFilter->streamPosition)->toBe(2);
})->with(['int limit' => [100, 1000]], ['null limit' => [null]]);

it('keep iterate when stream iterator raise stream not found exception', function () {
    $queryFilter = $this->createMock(ProjectionQueryFilter::class);
    $queryFilter->expects($this->exactly(2))->method('setStreamPosition');

    $streamEvents = StreamEventsFactory::fromEmptyAndRaiseStreamNotFoundException('customer-123');
    $streamEvents2 = getStreamEvents();

    $this->option->expects($this->never())->method('getLoadLimiter');
    $this->context->expects($this->once())->method('queryFilter')->willReturn($queryFilter);
    $this->subscription->expects($this->once())->method('context')->willReturn($this->context);
    $this->streamManager->expects($this->once())->method('jsonSerialize')->willReturn(['customer-123' => 1, 'customer-456' => 20]);
    $this->chronicler->expects($this->exactly(2))->method('retrieveFiltered')->willReturnOnConsecutiveCalls($streamEvents, $streamEvents2);

    $this->subscription->expects($this->once())->method('setStreamIterator')->with(
        $this->callback(fn ($iterator) => $iterator instanceof MergeStreamIterator)
    );

    $next = ($this->activity)($this->subscription, $this->next);

    expect($next())->toBe(42);
});

it('set current position on query filter incremented by one', function () {
    // with many streams, merge stream iterator prioritize in constructor
    $this->subscription->expects($this->once())->method('clock')->willReturn(new PointInTime());

    $queryFilter = $this->createMock(ProjectionQueryFilter::class);

    $matcher = $this->exactly(2);
    $queryFilter->expects($matcher)->method('setStreamPosition')
        ->willReturnCallback(function (int $position) use ($matcher) {
            match ($matcher->numberOfInvocations()) {
                1 => expect($position)->toBe(2),
                2 => expect($position)->toBe(21),
                default => null,
            };
        });

    $streamEvents = getStreamEvents();
    $streamEvents2 = getStreamEvents();

    $this->context->expects($this->once())->method('queryFilter')->willReturn($queryFilter);
    $this->subscription->expects($this->once())->method('context')->willReturn($this->context);
    $this->streamManager->expects($this->once())->method('jsonSerialize')->willReturn(['customer-123' => 1, 'customer-456' => 20]);
    $this->chronicler->expects($this->exactly(2))->method('retrieveFiltered')->willReturnOnConsecutiveCalls($streamEvents, $streamEvents2);

    $this->subscription->expects($this->once())->method('setStreamIterator')->with(
        $this->callback(fn ($iterator) => $iterator instanceof MergeStreamIterator)
    );

    $next = ($this->activity)($this->subscription, $this->next);

    expect($next())->toBe(42);
});
