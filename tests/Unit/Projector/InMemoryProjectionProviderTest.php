<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Chronhub\Storm\Clock\PointInTimeFactory;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\ProjectionModel;
use Chronhub\Storm\Projector\Exceptions\InMemoryProjectionFailed;
use Chronhub\Storm\Projector\Exceptions\ProjectionAlreadyRunning;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;
use Chronhub\Storm\Projector\InMemoryProjection;
use Chronhub\Storm\Projector\InMemoryProjectionProvider;
use Chronhub\Storm\Projector\ProjectionStatus;

beforeEach(function (): void {
    $this->clock = $this->createMock(SystemClock::class);
    $this->provider = new InMemoryProjectionProvider($this->clock);
});

test('in memory projection provider instance', function () {
    $this->assertNull($this->provider->retrieve('customer'));
});

test('create projection with status', function (ProjectionStatus $status): void {
    $this->provider->createProjection('customer', $status->value);

    expect($this->provider->exists('customer'))->toBeTrue();

    $projection = $this->provider->retrieve('customer');

    expect($projection)->toBeInstanceOf(ProjectionModel::class)
        ->and($projection)->toBeInstanceOf(InMemoryProjection::class)
        ->and($projection->name())->toBe('customer')
        ->and($projection->state())->toEqual('{}')
        ->and($projection->position())->toEqual('{}')
        ->and($projection->status())->toEqual($status->value)
        ->and($projection->lockedUntil())->toBeNull();
})->with('projection status');

test('acquire lock with status', function (ProjectionStatus $status): void {
    $nowToString = PointInTimeFactory::nowToString();

    $this->clock->expects($this->never())->method('isGreaterThan');

    $this->provider->createProjection('customer', 'running');

    $this->provider->acquireLock('customer', $status->value, $nowToString);

    $projection = $this->provider->retrieve('customer');

    expect($projection->status())
        ->toBe($status->value)
        ->and($projection->lockedUntil())->toBe($nowToString);
})->with('projection status');

test('acquire lock failed when another process has lock', function (): void {
    $lockedUntil = PointInTimeFactory::nowToString();

    $this->clock->expects($this->once())->method('isGreaterThanNow')->with($lockedUntil)->willReturn(false);

    $this->provider->createProjection('customer', 'idle');
    expect($this->provider->retrieve('customer')->lockedUntil())->toBeNull();

    $this->provider->acquireLock('customer', 'running', $lockedUntil);
    expect($this->provider->retrieve('customer')->lockedUntil())->toBe($lockedUntil);

    $this->provider->acquireLock('customer', 'running', $lockedUntil);
})->throws(ProjectionAlreadyRunning::class, 'Acquiring lock failed for stream name: customer: another projection process is already running or wait till the stopping process complete');

test('acquire lock with projection not found', function (): void {
    $this->provider->acquireLock('customer', 'running', PointInTimeFactory::nowToString());
})->throws(ProjectionNotFound::class);

test('update projection with projection not locked', function (): void {
    $this->clock->expects($this->never())->method('isGreaterThan');

    $this->provider->createProjection('customer', 'idle');

    $this->provider->updateProjection('customer', 'running');
})->throws(InMemoryProjectionFailed::class, 'Projection lock must be acquired before updating projection customer');

test('delete projection', function (): void {
    $this->provider->createProjection('customer', 'idle');

    expect($this->provider->exists('customer'))->toBeTrue();

    $this->provider->deleteProjection('customer');

    expect($this->provider->exists('customer'))->toBeFalse();
});

test('delete projection with projection not found', function (): void {
    expect($this->provider->exists('customer'))->toBeFalse();

    $this->provider->deleteProjection('customer');
})->throws(ProjectionNotFound::class);

test('update projection', function (): void {
    $this->provider->createProjection('customer', 'idle');

    $lockedUntil = PointInTimeFactory::nowToString();
    $this->provider->acquireLock('customer', 'running', PointInTimeFactory::nowToString());

    $newLockedUntil = PointInTimeFactory::nowToString();
    $this->provider->updateProjection('customer', 'running', '{"foo":"bar"}', '{"foo":"bar"}', $newLockedUntil);

    $projection = $this->provider->retrieve('customer');

    expect($projection->status())->toBe('running')
        ->and($projection->state())->toBe('{"foo":"bar"}')
        ->and($projection->position())->toBe('{"foo":"bar"}')
        ->and($projection->lockedUntil())->toBe($newLockedUntil);
});

test('update projection with locked until', function (string|false|null $lockedUntil) {
    $this->provider->createProjection('customer', 'idle');

    $currentLock = PointInTimeFactory::nowToString();
    $this->provider->acquireLock('customer', 'running', $currentLock);

    $this->provider->updateProjection('customer', lockedUntil: $lockedUntil);

    $projection = $this->provider->retrieve('customer');

    if ($lockedUntil === null) {
        expect($projection->lockedUntil())->toBeNull();
    } elseif ($lockedUntil === false) {
        expect($projection->lockedUntil())->toBe($currentLock);
    } else {
        expect($projection->lockedUntil())->toBe($lockedUntil);
    }
})->with([
    'locked until as string' => PointInTimeFactory::nowToString(),
    'locked until as false' => false,
    'locked until as null' => null,
]);

test('filter by names in the requested order', function (): void {
    $this->provider->createProjection('customer', 'idle');
    $this->provider->createProjection('order', 'idle');

    $projectionNames = $this->provider->filterByNames('customer', 'order');

    expect($projectionNames)
        ->toHaveCount(2)
        ->and($projectionNames)->toBe(['customer', 'order']);
});

test('filter non existent requested names', function (): void {
    $this->provider->createProjection('customer', 'idle');
    $this->provider->createProjection('order', 'idle');

    $projectionNames = $this->provider->filterByNames('customer', 'order', 'foo');

    expect($projectionNames)
        ->toHaveCount(2)
        ->and($projectionNames)->toBe(['customer', 'order']);
});

it('filter by names return empty array', function (): void {
    $this->provider->createProjection('customer', 'idle');
    $this->provider->createProjection('order', 'idle');

    $projectionNames = $this->provider->filterByNames('foo', 'bar');

    expect($projectionNames)->toBeEmpty();
});

test('projection exists by name', function (): void {
    $this->provider->createProjection('customer', 'idle');

    expect($this->provider->exists('customer'))->toBeTrue()
        ->and($this->provider->exists('foo'))->toBeFalse();
});
