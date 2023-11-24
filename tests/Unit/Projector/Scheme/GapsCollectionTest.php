<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Projector\Scheme\GapCollection;

beforeEach(function (): void {
    $this->gaps = new GapCollection();
});

test('new instance', function (): void {
    expect($this->gaps->all())->toBeEmpty()
        ->and($this->gaps->all())->not()->toBe($this->gaps->all())
        ->and($this->gaps->all())->toEqual($this->gaps->all());
});

test('put gap', function (): void {
    $this->gaps->put(1, true);
    $this->gaps->put(2, false);

    expect($this->gaps->all())->toHaveCount(2)
        ->and($this->gaps->all()->toArray())->toEqual([1 => true, 2 => false]);
});

test('put gap with zero position', function (): void {
    $this->gaps->put(0, true);
})->throws(InvalidArgumentException::class, 'Event position must be greater than 0');

test('remove gap', function (): void {
    $this->gaps->put(1, true);
    $this->gaps->put(2, false);

    expect($this->gaps->all())->toHaveCount(2)
        ->and($this->gaps->all()->toArray())->toEqual([1 => true, 2 => false]);

    $this->gaps->remove(1);

    expect($this->gaps->all()->toArray())->toHaveCount(1)
        ->and($this->gaps->all()->toArray())->toEqual([2 => false]);
});

test('remove gape with unknown position', function (): void {
    $this->gaps->put(1, true);
    $this->gaps->put(2, false);

    expect($this->gaps->all())->toHaveCount(2)
        ->and($this->gaps->all()->toArray())->toEqual([1 => true, 2 => false]);

    $this->gaps->remove(3);

    expect($this->gaps->all()->toArray())->toEqual([1 => true, 2 => false]);
});

test('merge gaps', function (): void {
    $this->gaps->put(1, true);
    $this->gaps->put(2, false);

    expect($this->gaps->all())->toHaveCount(2)
        ->and($this->gaps->all()->toArray())->toEqual([1 => true, 2 => false]);

    $this->gaps->merge([3, 4]);

    expect($this->gaps->all()->toArray())->toHaveCount(4)
        ->and($this->gaps->all()->toArray())->toEqual([1 => true, 2 => false, 3 => true, 4 => true]);
});

test('merge gaps with empty array', function (): void {
    $this->gaps->put(1, true);
    $this->gaps->put(2, false);

    expect($this->gaps->all())->toHaveCount(2)
        ->and($this->gaps->all()->toArray())->toEqual([1 => true, 2 => false]);

    $this->gaps->merge([]);

    expect($this->gaps->all()->toArray())->toHaveCount(2)
        ->and($this->gaps->all()->toArray())->toEqual([1 => true, 2 => false]);
});

test('merge gaps with associative array', function (): void {
    $this->gaps->put(1, true);
    $this->gaps->put(2, false);

    expect($this->gaps->all())->toHaveCount(2)
        ->and($this->gaps->all()->toArray())->toEqual([1 => true, 2 => false]);

    $this->gaps->merge(['4' => true, '5' => true]);
})->throws(InvalidArgumentException::class, 'Stream gaps must be not be an associative array');

test('merge gaps with zero position', function (): void {
    $this->gaps->put(1, true);
    $this->gaps->put(2, false);

    $this->gaps->merge([0, 1]);
})->throws(InvalidArgumentException::class, 'Event position must be greater than 0');

test('merge and overwrite local gaps', function () {
    $this->gaps->put(1, true);
    $this->gaps->put(2, false);

    $this->gaps->merge([2, 3, 4]);

    expect($this->gaps->all()->toArray())->toHaveCount(4)
        ->and($this->gaps->all()->toArray())->toEqual([1 => true, 2 => true, 3 => true, 4 => true]);
});

test('filter confirmed gaps alter local gaps', function (): void {
    $this->gaps->put(1, true);
    $this->gaps->put(2, false);
    $this->gaps->put(3, true);

    expect($this->gaps->all())->toHaveCount(3)
        ->and($this->gaps->all()->toArray())->toEqual([1 => true, 2 => false, 3 => true]);

    $gaps = $this->gaps->filterConfirmedGaps();

    expect($gaps)->toHaveCount(2)->and($gaps)->toEqual([1, 3])
        ->and($this->gaps->all()->toArray())->toEqual([1 => true, 3 => true]);
});
