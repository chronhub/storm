<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Chronhub\Storm\Projector\Exceptions\ProjectionFailed;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Tests\Uses\TestingProjectorMonitor;
use RuntimeException;
use Throwable;

uses(TestingProjectorMonitor::class);

beforeEach(function () {
    $this->setupProjectorMonitor();
});

dataset('status', [
    'stopping' => [ProjectionStatus::STOPPING->value],
    'resetting' => [ProjectionStatus::RESETTING->value],
    'deleting' => [ProjectionStatus::DELETING->value],
    'deleting_with_emitted_events' => [ProjectionStatus::DELETING_WITH_EMITTED_EVENTS->value],
]);

dataset('getter', [
    'statusOf' => ['status'],
    'streamPositionsOf' => ['positions'],
    'stateOf' => ['state'],
]);

it('can mark projection with status', function (string $status) {
    $this->projections->expects($this->once())->method('updateProjection')->with('projection_name', $status);

    $this->callUpdate($status);
})->with('status');

it('can get data projection', function (string $get) {
    $this->projections->expects($this->once())->method('retrieve')->with('projection_name')->willReturn($this->model);

    $this->callGetter($get);
})->with('getter');

describe('it fails update status on exception', function () {

    test('of projection not found', function (string $status) {
        $exception = ProjectionNotFound::withName('projection_name');

        $this->projections->expects($this->once())
            ->method('updateProjection')
            ->with('projection_name', $status)
            ->willThrowException($exception);

        try {
            $this->callUpdate($status);
        } catch (Throwable $e) {
            expect($e)->toBe($exception);
        }
    })->with('status');

    test('of projection failed', function (string $status) {
        $exception = new ProjectionFailed('some error');

        $this->projections->expects($this->once())
            ->method('updateProjection')
            ->with('projection_name', $status)
            ->willThrowException($exception);

        try {
            $this->callUpdate($status);
        } catch (Throwable $e) {
            expect($e)->toBe($exception);
        }
    })->with('status');

    test('of any other exception', function (string $status) {
        $exception = new RuntimeException('some error');

        $this->projections->expects($this->once())
            ->method('updateProjection')
            ->with('projection_name', $status)
            ->willThrowException($exception);

        try {
            $this->callUpdate($status);
        } catch (Throwable $wrapException) {
            expect($wrapException)->toBeInstanceOf(ProjectionFailed::class)
                ->and($wrapException->getMessage())->toBe("Unable to update projection status for stream name projection_name and status $status");
        }
    })->with('status');
});

it('can not get field of projection not found', function (string $getter) {
    $this->projections->expects($this->once())->method('retrieve')->with('projection_name')->willReturn(null);

    $this->failGetter($getter);
})
    ->with('getter')
    ->throws(ProjectionNotFound::class, 'Projection projection_name not found');

it('can filter projection names', function () {
    $this->projections->expects($this->once())->method('filterByNames')->with('foo', 'bar')->willReturn(['foo', 'bar']);

    $this->projectorMonitor->filterNames('foo', 'bar');
});

it('can check if projection exists', function (bool $exists) {
    $this->projections->expects($this->once())->method('exists')->with('projection_name')->willReturn($exists);

    expect($this->projectorMonitor->exists('projection_name'))->toBe($exists);
})->with(['exists' => [true], 'not exists' => [false]]);
