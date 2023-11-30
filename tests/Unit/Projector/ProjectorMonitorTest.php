<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Chronhub\Storm\Contracts\Projector\ProjectionModel;
use Chronhub\Storm\Contracts\Projector\ProjectionProvider;
use Chronhub\Storm\Contracts\Serializer\JsonSerializer;
use Chronhub\Storm\Projector\Exceptions\ProjectionFailed;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\ProjectorMonitor;
use RuntimeException;
use Throwable;

beforeEach(function () {
    $this->projections = $this->createMock(ProjectionProvider::class);
    $this->serializer = $this->createMock(JsonSerializer::class);
    $this->model = $this->createMock(ProjectionModel::class);
    $this->projectorMonitor = new ProjectorMonitor($this->projections, $this->serializer);
});

dataset('status', [
    'stopping' => [ProjectionStatus::STOPPING->value],
    'resetting' => [ProjectionStatus::RESETTING->value],
    'deleting' => [ProjectionStatus::DELETING->value],
    'deleting_with_emitted_events' => [ProjectionStatus::DELETING_WITH_EMITTED_EVENTS->value],
]);

dataset('getter', [
    'status' => ['status'],
    'positions' => ['positions'],
    'state' => ['state'],
]);

$update = function (string $status) {
    switch ($status) {
        case 'stopping':
            $this->projectorMonitor->markAsStop('projection_name');

            break;
        case 'resetting':
            $this->projectorMonitor->markAsReset('projection_name');

            break;
        case 'deleting':
            $this->projectorMonitor->markAsDelete('projection_name', false);

            break;
        case 'deleting_with_emitted_events':
            $this->projectorMonitor->markAsDelete('projection_name', true);

            break;
    }
};

$getter = function (string $getter) {
    switch ($getter) {
        case 'status':
            $this->model->expects($this->once())->method('status')->willReturn('running');
            expect($this->projectorMonitor->statusOf('projection_name'))->toBe('running');

            break;
        case 'positions':
            $this->model->expects($this->once())->method('positions')->willReturn('{"foo": 1}');
            $this->serializer->expects($this->once())->method('decode')->with('{"foo": 1}')->willReturn(['foo' => 1]);
            expect($this->projectorMonitor->streamPositionsOf('projection_name'))->toBe(['foo' => 1]);

            break;
        case 'state':
            $this->model->expects($this->once())->method('state')->willReturn('{"count": 1}');
            $this->serializer->expects($this->once())->method('decode')->with('{"count": 1}')->willReturn(['count' => 1]);
            expect($this->projectorMonitor->stateOf('projection_name'))->toBe(['count' => 1]);

            break;
    }
};

test('can mark projection as', function (string $status) use ($update) {
    $this->projections->expects($this->once())->method('updateProjection')->with('projection_name', $status);

    $update->call($this, $status);
})->with('status');

test('can get data projection of', function (string $get) use ($getter) {
    $this->projections->expects($this->once())->method('retrieve')->with('projection_name')->willReturn($this->model);

    $getter->call($this, $get);
})->with('getter');

describe('it fail update status on exception', function () use ($update) {
    test('of projection not found', function (string $status) use ($update) {
        $exception = ProjectionNotFound::withName('projection_name');
        $this->projections->expects($this->once())
            ->method('updateProjection')
            ->with('projection_name', $status)
            ->willThrowException($exception);

        try {
            $update->call($this, $status);
        } catch (Throwable $e) {
            expect($e)->toBe($exception);
        }
    })->with('status');

    test('of projection failed', function (string $status) use ($update) {
        $exception = new ProjectionFailed('some error');
        $this->projections->expects($this->once())
            ->method('updateProjection')
            ->with('projection_name', $status)
            ->willThrowException($exception);

        try {
            $update->call($this, $status);
        } catch (Throwable $e) {
            expect($e)->toBe($exception);
        }
    })->with('status');

    test('of any other exception', function (string $status) use ($update) {
        $exception = new RuntimeException('some error');
        $this->projections->expects($this->once())
            ->method('updateProjection')
            ->with('projection_name', $status)
            ->willThrowException($exception);

        try {
            $update->call($this, $status);
        } catch (Throwable $wrapException) {
            expect($wrapException)->toBeInstanceOf(ProjectionFailed::class)
                ->and($wrapException->getMessage())->toBe("Unable to update projection status for stream name projection_name and status $status");
        }
    })->with('status');
});

it('can not get status of projection not found', function () {
    $this->projections->expects($this->once())->method('retrieve')->with('projection_name')->willReturn(null);

    $this->projectorMonitor->statusOf('projection_name');
})->throws(ProjectionNotFound::class, 'Projection projection_name not found');

it('can not get stream positions of projection not found', function () {
    $this->projections->expects($this->once())->method('retrieve')->with('projection_name')->willReturn(null);

    $this->projectorMonitor->streamPositionsOf('projection_name');
})->throws(ProjectionNotFound::class, 'Projection projection_name not found');

it('can not get projection state if projection not found', function () {
    $this->projections->expects($this->once())->method('retrieve')->with('projection_name')->willReturn(null);

    $this->projectorMonitor->stateOf('projection_name');
})->throws(ProjectionNotFound::class, 'Projection projection_name not found');

it('can filter projection names', function () {
    $this->projections->expects($this->once())->method('filterByNames')->with('foo', 'bar')->willReturn(['foo', 'bar']);

    $this->projectorMonitor->filterNames('foo', 'bar');
});

test('can check if projection exists', function (bool $exists) {
    $this->projections->expects($this->once())->method('exists')->with('projection_name')->willReturn($exists);

    expect($this->projectorMonitor->exists('projection_name'))->toBe($exists);
})->with(['exists' => [true], 'not exists' => [false]]);
