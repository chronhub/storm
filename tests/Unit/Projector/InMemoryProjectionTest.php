<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Chronhub\Storm\Contracts\Projector\ProjectionModel;
use Chronhub\Storm\Projector\InMemoryProjection;

test('create projection', function (): void {
    $projection = InMemoryProjection::create('projection', 'running');

    expect($projection)->toBeInstanceOf(ProjectionModel::class)
        ->and($projection->name())->toBe('projection')
        ->and($projection->status())->toBe('running')
        ->and($projection->positions())->toBe('{}')
        ->and($projection->state())->toBe('{}')
        ->and($projection->lockedUntil())->toBeNull();
});

test('update state', function (): void {
    $projection = InMemoryProjection::create('projection', 'running');

    expect($projection->state())->toBe('{}');

    $projection->setState('{"count":1}');

    expect($projection->state())->toBe('{"count":1}');
});

test('update position', function (): void {
    $projection = InMemoryProjection::create('projection', 'running');

    expect($projection->positions())->toBe('{}');

    $projection->setPosition('{foo:1}');

    expect($projection->positions())->toBe('{foo:1}');
});

test('update status', function (): void {
    $projection = InMemoryProjection::create('projection', 'running');

    expect($projection->status())->toBe('running');

    $projection->setStatus('idle');

    expect($projection->status())->toBe('idle');
});

test('update locked until', function (?string $value): void {
    $projection = InMemoryProjection::create('projection', 'running');

    expect($projection->status())->toBe('running');

    $projection->setLockedUntil($value);

    expect($projection->lockedUntil())->toBe($value);
})->with(['string' => 'datetime',    'null' => null]);
