<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Iterator;

use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Projector\Iterator\StreamIterator;
use Chronhub\Storm\Tests\Factory\StreamEventsFactory;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Generator;

test('empty generator raised stream not found exception', function (): void {
    $streamEvents = StreamEventsFactory::fromEmpty();

    new StreamIterator($streamEvents);
})->throws(StreamNotFound::class, 'Stream not found');

test('pointer advance on constructor', function (): void {
    $streamEvents = StreamEventsFactory::fromInternalPosition(10);

    $iterator = new StreamIterator($streamEvents);

    expect($iterator->valid())->toBeTrue()
        ->and($iterator->current())->toBeInstanceOf(SomeEvent::class)
        ->and($iterator->key())->toBe(1);
});

test('iterate with internal position as key and event as value', function (): void {
    $streamEvents = StreamEventsFactory::fromInternalPosition(5);

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

test('rewind iterator', function (): void {
    $streamEvents = StreamEventsFactory::fromInternalPosition(10);

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

test('count events', function (Generator $streamEvents, int $expectedCount): void {
    $iterator = new StreamIterator($streamEvents);

    expect($iterator->count())->toBe($expectedCount);
})->with(
    [
        [fn () => StreamEventsFactory::fromInternalPosition(5), 5],
        [fn () => StreamEventsFactory::fromInternalPosition(10), 10],
        [fn () => StreamEventsFactory::fromInternalPosition(20), 20],
    ]);
