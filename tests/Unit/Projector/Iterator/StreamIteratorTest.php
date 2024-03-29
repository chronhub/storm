<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Iterator;

use Chronhub\Storm\Projector\Iterator\StreamIterator;
use Chronhub\Storm\Tests\Factory\StreamEventsFactory;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Generator;

test('empty generator set iterator as not valid', function (): void {
    $streamEvents = StreamEventsFactory::fromEmpty();

    $iterator = new StreamIterator($streamEvents);

    expect($iterator->valid())->toBeFalse()
        ->and($iterator->current())->toBeNull()
        ->and($iterator->key())->toBeNull();
});

test('iterator cursor advance on constructor', function (): void {
    $streamEvents = StreamEventsFactory::withEvent(SomeEvent::class)->fromInternalPosition(10);

    $iterator = new StreamIterator($streamEvents);

    expect($iterator->valid())->toBeTrue()
        ->and($iterator->current())->toBeInstanceOf(SomeEvent::class)
        ->and($iterator->key())->toBe(1);
});

test('can iterate with internal position as key and event as value', function (): void {
    $streamEvents = StreamEventsFactory::withEvent(SomeEvent::class)->fromInternalPosition(5);

    $iterator = new StreamIterator($streamEvents);

    $internalPosition = 1;

    while ($iterator->valid()) {
        expect($iterator->current())->toBeInstanceOf(SomeEvent::class)
            ->and($iterator->key())->toBe($internalPosition);

        $internalPosition++;

        $iterator->next();
    }

    expect($iterator->valid())->toBeFalse()
        ->and($iterator->current())->toBeNull()
        ->and($iterator->key())->toBeNull();
});

test('can rewind', function (): void {
    $streamEvents = StreamEventsFactory::withEvent(SomeEvent::class)->fromInternalPosition(10);

    $iterator = new StreamIterator($streamEvents);

    expect($iterator->current())->toBeInstanceOf(SomeEvent::class)
        ->and($iterator->key())->toBe(1);

    $iterator->next();
    $iterator->next();

    expect($iterator->current())->toBeInstanceOf(SomeEvent::class)
        ->and($iterator->key())->toBe(3);

    $iterator->rewind();

    expect($iterator->current())->toBeInstanceOf(SomeEvent::class)
        ->and($iterator->key())->toBe(1);
});

test('can rewind when iterator is no longer valid', function () {
    $streamEvents = StreamEventsFactory::withEvent(SomeEvent::class)->fromInternalPosition(10);

    $iterator = new StreamIterator($streamEvents);

    $iterator->next();
    $iterator->next();

    $iterator->rewind();

    expect($iterator->valid())->toBeTrue();

    while ($iterator->valid()) {
        $iterator->next();
    }

    expect($iterator->valid())->toBeFalse();

    $iterator->rewind();

    expect($iterator->valid())->toBeTrue();
});

test('count total of events', function (Generator $streamEvents, int $expectedCount): void {
    $iterator = new StreamIterator($streamEvents);

    expect($iterator->count())->toBe($expectedCount);
})->with(
    [
        [fn () => StreamEventsFactory::withEvent(SomeEvent::class)->fromInternalPosition(5), 5],
        [fn () => StreamEventsFactory::withEvent(SomeEvent::class)->fromInternalPosition(10), 10],
        [fn () => StreamEventsFactory::withEvent(SomeEvent::class)->fromInternalPosition(20), 20],
    ]);
