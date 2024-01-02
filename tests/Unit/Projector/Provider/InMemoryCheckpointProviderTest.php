<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Provider;

use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Projector\Provider\Checkpoint\CheckpointDTO;
use Chronhub\Storm\Projector\Provider\Checkpoint\InMemoryCheckpointModel;
use Chronhub\Storm\Projector\Provider\Checkpoint\InMemorySnapshotProvider;
use DateInterval;

use function sha1;

beforeEach(function (): void {
    $this->clock = new PointInTime();
    $this->provider = new InMemorySnapshotProvider($this->clock);
});

it('can be instantiated', function (): void {
    $this->assertEmpty($this->provider->all());
});

it('can add checkpoint', function () {
    $dto = new CheckpointDTO('projection', 'stream', 10, '2023-01-01 00:00:00', '{}');

    $this->provider->insert($dto);

    $this->assertCount(1, $this->provider->all());
});

it('produce unique id', function () {
    $dto = new CheckpointDTO('projection', 'stream', 10, '2023-01-01 00:00:00', '{}');

    $this->provider->insert($dto);

    $model = $this->provider->lastCheckpoint('projection', 'stream');
    expect($model->id())->toBe(sha1('projection:stream:10'));
});

it('can find last checkpoint', function () {
    $dto = new CheckpointDTO('projection', 'stream', 10, '2023-01-01 00:00:00', '{}');

    $this->provider->insert($dto);

    $this->assertCount(1, $this->provider->all());

    $checkpoint = $this->provider->lastCheckpoint('projection', 'stream');

    expect($checkpoint)->toBeInstanceOf(InMemoryCheckpointModel::class)
        ->and($checkpoint->projectionName)->toEqual('projection')
        ->and($checkpoint->streamName)->toEqual('stream')
        ->and($checkpoint->position)->toEqual(10)
        ->and($checkpoint->createdAt)->toEqual('2023-01-01 00:00:00')
        ->and($checkpoint->gaps)->toEqual('{}');
});

it('can find last checkpoint among many', function () {
    expect($this->provider->lastCheckpoint('projection', 'stream'))->toBeNull();

    $now = $this->clock->now();
    $next = $now->add(new DateInterval('PT10S'));

    $dto = new CheckpointDTO('projection', 'stream', 10, $this->clock->format($now), '{}');
    $dto2 = new CheckpointDTO('projection', 'stream', 20, $this->clock->format($next), '{}');

    $this->provider->insert($dto);
    $this->provider->insert($dto2);

    $this->assertCount(2, $this->provider->all());

    $checkpoint = $this->provider->lastCheckpoint('projection', 'stream');

    expect($checkpoint)->toBeInstanceOf(InMemoryCheckpointModel::class)
        ->and($checkpoint->projectionName)->toEqual('projection')
        ->and($checkpoint->streamName)->toEqual('stream')
        ->and($checkpoint->position)->toEqual(20)
        ->and($checkpoint->createdAt)->toEqual($this->clock->format($next))
        ->and($checkpoint->gaps)->toEqual('{}');
});

it('retrieve all unique and last checkpoints per projection', function () {
    $now = $this->clock->now();
    $next = $now->add(new DateInterval('PT10S'));
    $next2 = $now->add(new DateInterval('PT20S'));
    $next3 = $now->add(new DateInterval('PT30S'));

    $dto = new CheckpointDTO('projection', 'stream', 10, $this->clock->format($now), '{}');
    $dto2 = new CheckpointDTO('projection', 'stream', 20, $this->clock->format($next), '{}');
    $dto3 = new CheckpointDTO('projection', 'stream1', 20, $this->clock->format($next2), '{}');
    $dto4 = new CheckpointDTO('projection', 'stream1', 30, $this->clock->format($next3), '{}');

    $this->provider->insert($dto);
    $this->provider->insert($dto2);
    $this->provider->insert($dto3);
    $this->provider->insert($dto4);

    $this->assertCount(4, $this->provider->all());

    $checkpoints = $this->provider->lastCheckpointByProjectionName('projection');

    expect($checkpoints)->toHaveCount(2)
        ->and($checkpoints->first())->toBeInstanceOf(InMemoryCheckpointModel::class)
        ->and($checkpoints->first()->projectionName)->toEqual('projection')
        ->and($checkpoints->first()->streamName)->toEqual('stream')
        ->and($checkpoints->first()->position)->toEqual(20)
        ->and($checkpoints->first()->createdAt)->toEqual($this->clock->format($next))
        ->and($checkpoints->first()->gaps)->toEqual('{}')
        ->and($checkpoints->last())->toBeInstanceOf(InMemoryCheckpointModel::class)
        ->and($checkpoints->last()->projectionName)->toEqual('projection')
        ->and($checkpoints->last()->streamName)->toEqual('stream1')
        ->and($checkpoints->last()->position)->toEqual(30)
        ->and($checkpoints->last()->createdAt)->toEqual($this->clock->format($next3))
        ->and($checkpoints->last()->gaps)->toEqual('{}');
});

it('delete by projection name', function () {
    $now = $this->clock->now();
    $next = $now->add(new DateInterval('PT10S'));
    $next2 = $now->add(new DateInterval('PT20S'));
    $next3 = $now->add(new DateInterval('PT30S'));

    $dto = new CheckpointDTO('projection', 'stream', 10, $this->clock->format($now), '{}');
    $dto2 = new CheckpointDTO('projection', 'stream', 20, $this->clock->format($next), '{}');
    $dto3 = new CheckpointDTO('projection1', 'stream1', 20, $this->clock->format($next2), '{}');
    $dto4 = new CheckpointDTO('projection1', 'stream1', 30, $this->clock->format($next3), '{}');

    $this->provider->insert($dto);
    $this->provider->insert($dto2);
    $this->provider->insert($dto3);
    $this->provider->insert($dto4);

    expect($this->provider->all())->toHaveCount(4);

    $this->provider->delete('projection');

    expect($this->provider->all())->toHaveCount(2)
        ->and($this->provider->lastCheckpoint('projection', 'stream'))->toBeNull();
});

it('delete by projection name and stream name', function () {
    $now = $this->clock->now();
    $next = $now->add(new DateInterval('PT10S'));
    $next2 = $now->add(new DateInterval('PT20S'));
    $next3 = $now->add(new DateInterval('PT30S'));

    $dto = new CheckpointDTO('projection', 'stream', 10, $this->clock->format($now), '{}');
    $dto2 = new CheckpointDTO('projection', 'stream', 20, $this->clock->format($next), '{}');
    $dto3 = new CheckpointDTO('projection', 'stream1', 20, $this->clock->format($next2), '{}');
    $dto4 = new CheckpointDTO('projection', 'stream1', 30, $this->clock->format($next3), '{}');

    $this->provider->insert($dto);
    $this->provider->insert($dto2);
    $this->provider->insert($dto3);
    $this->provider->insert($dto4);

    expect($this->provider->all())->toHaveCount(4);

    $this->provider->deleteByNames('projection', 'stream');

    expect($this->provider->all())->toHaveCount(2);
});

it('delete all', function () {
    $now = $this->clock->now();
    $next = $now->add(new DateInterval('PT10S'));
    $next2 = $now->add(new DateInterval('PT20S'));
    $next3 = $now->add(new DateInterval('PT30S'));

    $dto = new CheckpointDTO('projection', 'stream', 10, $this->clock->format($now), '{}');
    $dto2 = new CheckpointDTO('projection', 'stream', 20, $this->clock->format($next), '{}');
    $dto3 = new CheckpointDTO('projection', 'stream1', 20, $this->clock->format($next2), '{}');
    $dto4 = new CheckpointDTO('projection', 'stream1', 30, $this->clock->format($next3), '{}');

    $this->provider->insert($dto);
    $this->provider->insert($dto2);
    $this->provider->insert($dto3);
    $this->provider->insert($dto4);

    expect($this->provider->all())->toHaveCount(4);

    $this->provider->deleteAll();

    expect($this->provider->all())->toHaveCount(0);
});
