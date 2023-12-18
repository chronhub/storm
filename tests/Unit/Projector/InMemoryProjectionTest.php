<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Chronhub\Storm\Contracts\Projector\ProjectionModel;
use Chronhub\Storm\Projector\Provider\InMemoryProjection;

it('create projection', function (): void {
    $projection = InMemoryProjection::create('projection', 'running');

    expect($projection)->toBeInstanceOf(ProjectionModel::class)
        ->and($projection->name())->toBe('projection')
        ->and($projection->status())->toBe('running')
        ->and($projection->position())->toBe('{}')
        ->and($projection->state())->toBe('{}')
        ->and($projection->lockedUntil())->toBeNull();
});

it('update state', function (): void {
    $projection = InMemoryProjection::create('projection', 'running');

    expect($projection->state())->toBe('{}');

    $projection->setState('{"count":1}');

    expect($projection->state())->toBe('{"count":1}');
});

it('update position', function (): void {
    $projection = InMemoryProjection::create('projection', 'running');

    expect($projection->position())->toBe('{}');

    $projection->setPosition('{foo:1}');

    expect($projection->position())->toBe('{foo:1}');
});

it('update status', function (): void {
    $projection = InMemoryProjection::create('projection', 'running');

    expect($projection->status())->toBe('running');

    $projection->setStatus('idle');

    expect($projection->status())->toBe('idle');
});

it('update locked until', function (?string $value): void {
    $projection = InMemoryProjection::create('projection', 'running');

    expect($projection->status())->toBe('running');

    $projection->setLockedUntil($value);

    expect($projection->lockedUntil())->toBe($value);
})->with(['string' => 'datetime', 'null' => null]);
