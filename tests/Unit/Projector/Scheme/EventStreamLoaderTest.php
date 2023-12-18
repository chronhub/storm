<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Projector\Provider\EventStreamLoader;

beforeEach(function () {
    $this->provider = $this->createMock(EventStreamProvider::class);
    $this->eventStreamLoader = new EventStreamLoader($this->provider);
});

describe('load from', function () {

    test('all', function () {
        $this->provider->expects($this->once())->method('allWithoutInternal')->willReturn(['stream1', 'stream2']);

        $result = $this->eventStreamLoader->loadFrom(['all' => true]);

        expect($result->toArray())->toBe(['stream1', 'stream2']);
    });

    test('categories', function () {
        $this->provider->expects($this->once())->method('filterByAscendantCategories')->with(['category1', 'category2'])->willReturn(['stream3', 'stream4']);

        $result = $this->eventStreamLoader->loadFrom(['categories' => ['category1', 'category2']]);

        expect($result->toArray())->toBe(['stream3', 'stream4']);
    });

    test('stream names', function () {
        $result = $this->eventStreamLoader->loadFrom(['names' => ['stream5', 'stream6']]);

        expect($result->toArray())->toBe(['stream5', 'stream6']);
    });
});

describe('raise exception when', function () {

    test('no stream set', function () {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No stream set or found');

        $this->eventStreamLoader->loadFrom([]);
    });

    test('return empty stream names', function () {
        $this->provider->expects($this->once())->method('allWithoutInternal')->willReturn([]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No stream set or found');

        $this->eventStreamLoader->loadFrom(['all' => true]);
    });

    test('return empty categories', function () {
        $this->provider->expects($this->once())->method('filterByAscendantCategories')->with(['category1', 'category2'])->willReturn([]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No stream set or found');

        $this->eventStreamLoader->loadFrom(['categories' => ['category1', 'category2']]);
    });

    test('return empty all', function () {
        $this->provider->expects($this->once())->method('allWithoutInternal')->willReturn([]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No stream set or found');

        $this->eventStreamLoader->loadFrom(['all' => true]);
    });

    test('duplicate stream names', function () {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Duplicate stream names is not allowed');

        $this->eventStreamLoader->loadFrom(['names' => ['duplicate', 'duplicate']]);
    });

    test('duplicate from categories', function () {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Duplicate stream names is not allowed');

        $this->provider->expects($this->once())->method('filterByAscendantCategories')->with(['foo', 'bar'])->willReturn(['duplicate', 'duplicate']);

        $this->eventStreamLoader->loadFrom(['categories' => ['foo', 'bar']]);
    });

    test('duplicate from all', function () {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Duplicate stream names is not allowed');

        $this->provider->expects($this->once())->method('allWithoutInternal')->willReturn(['duplicate', 'duplicate']);

        $this->eventStreamLoader->loadFrom(['all' => true]);
    });
});
