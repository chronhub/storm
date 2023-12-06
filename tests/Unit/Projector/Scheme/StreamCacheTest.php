<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Projector\Scheme\StreamCache;

test('stream cache instance', function (): void {
    $cache = new StreamCache(3);

    expect($cache->jsonSerialize())->toBe([0 => null, 1 => null, 2 => null]);
});

test('can push stream name', function (): void {
    $cache = new StreamCache(3);

    expect($cache->has('customer-123'))->toBeFalse();

    $cache->push('customer-123');

    expect($cache->has('customer-123'))->toBeTrue()
        ->and($cache->jsonSerialize())->toBe([0 => 'customer-123', 1 => null, 2 => null]);
});

test('can push stream name and replace oldest stream name according to cache size and position', function (): void {
    $cache = new StreamCache(2);

    $cache->push('customer-123');
    $cache->push('customer-456');

    expect($cache->jsonSerialize())->toBe(['customer-123', 'customer-456']);

    $cache->push('customer-789');

    expect($cache->jsonSerialize())->toBe([0 => 'customer-789', 1 => 'customer-456']);

    $cache->push('customer-012');

    expect($cache->jsonSerialize())->toBe([0 => 'customer-789', 1 => 'customer-012']);
});

test('can push stream with cache size of one', function (): void {
    $cache = new StreamCache(1);

    $cache->push('customer-123');

    expect($cache->has('customer-123'))->toBeTrue()
        ->and($cache->jsonSerialize())->toBe(['customer-123']);

    $cache->push('customer-456');

    expect($cache->has('customer-123'))->toBeFalse()
        ->and($cache->has('customer-456'))->toBeTrue()
        ->and($cache->jsonSerialize())->toBe(['customer-456']);
});

test('check if stream name is in cache', function (): void {
    $cache = new StreamCache(2);

    $cache->push('customer-123');
    $cache->push('customer-456');

    expect($cache->has('customer-123'))->toBeTrue()
        ->and($cache->has('customer-456'))->toBeTrue()
        ->and($cache->has('customer-789'))->toBeFalse();
});

test('raise exception when cache size is less than 1', function (int $cacheSize): void {
    new StreamCache($cacheSize);
})
    ->with(['zero' => [0], 'negative' => [-1, -5]])
    ->throws(InvalidArgumentException::class, 'Stream cache size must be greater than 0');

test('raise exception when push stream name already exists', function (array $streamNames): void {
    $cache = new StreamCache(3);

    foreach ($streamNames as $streamName) {
        $cache->push($streamName);
    }
})
    ->with([
        'empty cache' => [['customer-123', 'customer-123']],
        'alternate' => [['customer-123', 'customer-256', 'customer-123']],
        'rotation' => [['customer-123', 'customer-256', 'customer-478', 'customer-123']],
        'rotation_2' => [['customer-256', 'customer-123', 'customer-478', 'customer-123']],
    ])
    ->throws(InvalidArgumentException::class, 'Stream customer-123 is already in the cache');
