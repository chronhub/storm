<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Chronhub\Storm\Projector\Exceptions\ProjectionFailed;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;
use Chronhub\Storm\Tests\Uses\TestingProjectorMonitor;
use RuntimeException;
use Throwable;

uses(TestingProjectorMonitor::class);

beforeEach(function () {
    $this->setupProjectorMonitor();
});

dataset('getter', [
    'statusOf' => ['status'],
    'streamPositionsOf' => ['positions'],
    'stateOf' => ['state'],
]);

it('can mark projection with status', function (string $status) {
    $this->projections->expects($this->once())->method('updateProjection')->with($this->projectionName, $status);

    $this->callUpdate($status);
})->with('projection status monitored');

it('can get data projection', function (string $get) {
    $this->projections->expects($this->once())->method('retrieve')->with($this->projectionName)->willReturn($this->model);

    $this->callGetter($get);
})->with('getter');

describe('it fails update status on exception', function () {

    test('of projection not found', function (string $status) {
        $exception = ProjectionNotFound::withName($this->projectionName);

        $this->projections->expects($this->once())->method('updateProjection')->with($this->projectionName, $status)->willThrowException($exception);

        try {
            $this->callUpdate($status);
        } catch (Throwable $e) {
            expect($e)->toBe($exception);
        }
    })->with('projection status monitored');

    test('of projection failed', function (string $status) {
        $exception = new ProjectionFailed('some error');

        $this->projections->expects($this->once())->method('updateProjection')->with($this->projectionName, $status)->willThrowException($exception);

        try {
            $this->callUpdate($status);
        } catch (Throwable $e) {
            expect($e)->toBe($exception);
        }
    })->with('projection status monitored');

    test('of any other exception', function (string $status) {
        $exception = new RuntimeException('some error');

        $this->projections->expects($this->once())->method('updateProjection')->with($this->projectionName, $status)->willThrowException($exception);

        try {
            $this->callUpdate($status);
        } catch (Throwable $wrapException) {
            expect($wrapException)->toBeInstanceOf(ProjectionFailed::class)
                ->and($wrapException->getMessage())->toBe("Unable to update projection status for stream name $this->projectionName and status $status");
        }
    })->with('projection status monitored');
});

it('can not get field of projection not found', function (string $getter) {
    $this->projections->expects($this->once())->method('retrieve')->with($this->projectionName)->willReturn(null);

    $this->failGetter($getter);
})
    ->with('getter')
    ->throws(ProjectionNotFound::class);

it('can filter projection names', function () {
    $this->projections->expects($this->once())->method('filterByNames')->with('foo', 'bar')->willReturn(['foo', 'bar']);

    $this->projectorMonitor->filterNames('foo', 'bar');
});

it('can check if projection exists', function (bool $exists) {
    $this->projections->expects($this->once())->method('exists')->with($this->projectionName)->willReturn($exists);

    expect($this->projectorMonitor->exists($this->projectionName))->toBe($exists);
})->with(['exists' => [true], 'not exists' => [false]]);
