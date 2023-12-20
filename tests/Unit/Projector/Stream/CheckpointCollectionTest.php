<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Stream;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Projector\Stream\CheckpointCollection;
use Chronhub\Storm\Projector\Stream\CheckpointFactory;
use Illuminate\Support\Collection;

beforeEach(function () {
    $this->clock = $this->createMock(SystemClock::class);
    $this->collection = new CheckpointCollection($this->clock);
});

it('test instance', function () {
    expect($this->collection)->toBeInstanceOf(CheckpointCollection::class)
        ->and($this->collection->all())->toBeInstanceOf(Collection::class)
        ->and($this->collection->all())->toBeEmpty();
});

it('discover new checkpoint', function () {
    $this->clock->expects($this->once())->method('toString')->willReturn('now');

    $this->collection->onDiscover('customer');

    $checkpoint = $this->collection->last('customer');

    expect($checkpoint->streamName)->toEqual('customer')
        ->and($checkpoint->position)->toEqual(0)
        ->and($checkpoint->gaps)->toEqual([])
        ->and($checkpoint->createdAt)->toEqual('now');
});

it('does not discover new checkpoint if it already exists', function () {
    $this->clock->expects($this->any())->method('toString')->willReturn('now');

    $this->collection->next('customer', 10, [5]);

    $this->collection->onDiscover('customer');

    $checkpoint = $this->collection->last('customer');

    expect($checkpoint->streamName)->toEqual('customer')
        ->and($checkpoint->position)->toEqual(10)
        ->and($checkpoint->gaps)->toEqual([5])
        ->and($checkpoint->createdAt)->toEqual('now');
});

it('return new checkpoint', function () {
    $this->clock->expects($this->once())->method('toString')->willReturn('now');

    $this->collection->onDiscover('customer');

    $checkpoint = $this->collection->last('customer');

    expect($checkpoint->streamName)->toEqual('customer')
        ->and($checkpoint->position)->toEqual(0)
        ->and($checkpoint->gaps)->toEqual([])
        ->and($checkpoint->createdAt)->toEqual('now');
});

it('find checkpoint', function () {
    $this->clock->expects($this->once())->method('toString')->willReturn('now');

    $this->collection->next('customer', 10, [1, 2]);

    $checkpoint = $this->collection->last('customer');

    expect($checkpoint->streamName)->toEqual('customer')
        ->and($checkpoint->position)->toEqual(10)
        ->and($checkpoint->gaps)->toEqual([1, 2])
        ->and($checkpoint->createdAt)->toEqual('now');
});

it('create new checkpoint', function () {
    $this->clock->expects($this->once())->method('toString')->willReturn('now');

    $this->collection->next('customer', 10, [1, 2]);

    $checkpoint = $this->collection->last('customer');

    expect($checkpoint->streamName)->toEqual('customer')
        ->and($checkpoint->position)->toEqual(10)
        ->and($checkpoint->gaps)->toEqual([1, 2])
        ->and($checkpoint->createdAt)->toEqual('now');
});

it('create new checkpoint with gap', function () {
    $this->clock->expects($this->exactly(2))->method('toString')->willReturn('now');

    $this->collection->next('customer', 4, []);
    $checkpoint = $this->collection->last('customer');

    $this->collection->nextWithGap($checkpoint, 6);

    $checkpointWithGap = $this->collection->last('customer');

    expect($checkpointWithGap->streamName)->toEqual('customer')
        ->and($checkpointWithGap->position)->toEqual(6)
        ->and($checkpointWithGap->gaps)->toEqual([5])
        ->and($checkpointWithGap->createdAt)->toEqual('now');
});

it('create new checkpoint with many gaps', function () {
    $this->clock->expects($this->exactly(2))->method('toString')->willReturn('now');

    // the last checkpoint position is 4
    $this->collection->next('customer', 4, []);
    $checkpoint = $this->collection->last('customer');

    // the current position of event is 10
    $this->collection->nextWithGap($checkpoint, 10);

    $checkpointWithGap = $this->collection->last('customer');

    expect($checkpointWithGap->streamName)->toEqual('customer')
        ->and($checkpointWithGap->position)->toEqual(10)
        ->and($checkpointWithGap->gaps)->toEqual([5, 6, 7, 8, 9])
        ->and($checkpointWithGap->createdAt)->toEqual('now');
});

it('raise exception when create gap with invalid position', function (int $noGap) {
    $this->clock->expects($this->any())->method('toString')->willReturn('now');

    $this->collection->next('customer', 4, []);
    $checkpoint = $this->collection->last('customer');

    $this->collection->nextWithGap($checkpoint, $noGap);
})
    ->with(['invalid position' => [3, 4]])
    ->throws(InvalidArgumentException::class);

it('update checkpoint', function () {
    $this->clock->expects($this->exactly(1))->method('toString')->willReturn('now');

    $this->collection->next('customer', 10, [1, 2]);

    $checkpoint = CheckpointFactory::from('customer', 11, 'now', [1, 2]);

    $this->collection->update('customer', $checkpoint);

    $checkpoint = $this->collection->last('customer');

    expect($checkpoint->streamName)->toEqual('customer')
        ->and($checkpoint->position)->toEqual(11)
        ->and($checkpoint->gaps)->toEqual([1, 2])
        ->and($checkpoint->createdAt)->toEqual('now');
});
