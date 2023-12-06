<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Repository;

use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Repository\Event\ProjectionCreated;
use Chronhub\Storm\Projector\Repository\Event\ProjectionDeleted;
use Chronhub\Storm\Projector\Repository\Event\ProjectionDeletedWithEvents;
use Chronhub\Storm\Projector\Repository\Event\ProjectionReset;
use Chronhub\Storm\Projector\Repository\Event\ProjectionRestarted;
use Chronhub\Storm\Projector\Repository\Event\ProjectionStarted;
use Chronhub\Storm\Projector\Repository\Event\ProjectionStopped;
use Chronhub\Storm\Projector\Repository\EventDispatcherRepository;
use Chronhub\Storm\Projector\Repository\ProjectionDetail;
use Chronhub\Storm\Tests\Uses\TestingEventDispatcherRepository;
use Illuminate\Events\Dispatcher;
use RuntimeException;

uses(TestingEventDispatcherRepository::class);

beforeEach(function (): void {
    $this->eventDispatcher = new Dispatcher();
    $this->repository = $this->createMock(ProjectionRepositoryInterface::class);
    $this->eventRepository = new EventDispatcherRepository($this->repository, $this->eventDispatcher);
});

test('dispatch event when', function (string $event) {
    $this->repository->expects($this->any())->method('projectionName')->willReturn($this->streamName);

    $dispatchEvent = function () use ($event): void {
        $status = ProjectionStatus::RUNNING;

        switch ($event) {
            case ProjectionCreated::class:
                $this->repository->expects($this->once())->method('create')->with($status);
                $this->eventRepository->create($status);

                break;
            case ProjectionStarted::class:
                $this->repository->expects($this->once())->method('start')->with($status);
                $this->eventRepository->start($status);

                break;
            case ProjectionStopped::class:
                $data = new ProjectionDetail([], []);
                $this->repository->expects($this->once())->method('stop')->with($data, $status);
                $this->eventRepository->stop($data, $status);

                break;
            case ProjectionRestarted::class:
                $this->repository->expects($this->once())->method('startAgain')->with($status);
                $this->eventRepository->startAgain($status);

                break;
            case ProjectionReset::class:
                $data = new ProjectionDetail([], []);
                $this->repository->expects($this->once())->method('reset')->with($data, $status);
                $this->eventRepository->reset($data, $status);

                break;
            case ProjectionDeleted::class:
                $this->repository->expects($this->once())->method('delete')->with(false);
                $this->eventRepository->delete(false);

                break;
            case ProjectionDeletedWithEvents::class:
                $this->repository->expects($this->once())->method('delete')->with(true);
                $this->eventRepository->delete(true);

                break;
            default:
                $this->assertTrue(false);
        }
    };

    $this->assertEventDispatched($event, $dispatchEvent);
})->with('projection dispatcher events');

test('dispatch error event on exception and raise it when', function (string $event) {
    $this->repository->expects($this->any())->method('projectionName')->willReturn($this->streamName);
    $exception = new RuntimeException('error');
    $status = ProjectionStatus::RUNNING;

    $dispatchEvent = function () use ($event, $status, $exception): void {
        switch ($event) {
            case ProjectionCreated::class:
                $this->repository->expects($this->once())->method('create')->with($status)->willThrowException($exception);
                $this->eventRepository->create($status);

                break;
            case ProjectionStarted::class:
                $this->repository->expects($this->once())->method('start')->with($status)->willThrowException($exception);
                $this->eventRepository->start($status);

                break;
            case ProjectionStopped::class:
                $data = new ProjectionDetail([], []);
                $this->repository->expects($this->once())->method('stop')->with($data, $status)->willThrowException($exception);
                $this->eventRepository->stop($data, $status);

                break;

            case ProjectionRestarted::class:
                $this->repository->expects($this->once())->method('startAgain')->with($status)->willThrowException($exception);
                $this->eventRepository->startAgain($status);

                break;
            case ProjectionReset::class:
                $data = new ProjectionDetail([], []);
                $this->repository->expects($this->once())->method('reset')->with($data, $status)->willThrowException($exception);
                $this->eventRepository->reset($data, $status);

                break;
            case ProjectionDeleted::class:
                $this->repository->expects($this->once())->method('delete')->with(false)->willThrowException($exception);
                $this->eventRepository->delete(false);

                break;
            case ProjectionDeletedWithEvents::class:
                $this->repository->expects($this->once())->method('delete')->with(true)->willThrowException($exception);
                $this->eventRepository->delete(true);

                break;
            default:
                $this->assertTrue(false);
        }
    };

    $this->assertErrorEventDispatched($event, $dispatchEvent, $exception);
})->with('projection dispatcher events');

it('persist', function (ProjectionStatus $status) {
    $data = new ProjectionDetail([], []);

    $this->repository->expects($this->once())->method('persist')->with($data, $status);

    $this->assertNoEventDispatched(fn () => $this->eventRepository->persist($data, $status));
})->with('projection status');

it('update lock', function () {
    $this->repository->expects($this->once())->method('updateLock');

    $this->assertNoEventDispatched(fn () => $this->eventRepository->updateLock());
});

it('load status', function (ProjectionStatus $status) {
    $this->repository->expects($this->once())->method('loadStatus')->willReturn($status);

    $this->assertNoEventDispatched(fn () => expect($this->eventRepository->loadStatus())->toBe($status));
})->with('projection status');

it('load detail', function () {
    $data = new ProjectionDetail([], []);

    $this->repository->expects($this->once())->method('loadDetail')->willReturn($data);

    $this->assertNoEventDispatched(fn () => expect($this->eventRepository->loadDetail())->toBe($data));
});

it('assert projection exists', function (bool $exists) {
    $this->repository->expects($this->once())->method('exists')->willReturn($exists);

    $this->assertNoEventDispatched(fn () => expect($this->eventRepository->exists())->toBe($exists));
})->with([['exists' => true, 'not exists' => false]]);

it('return projection name', function (string $name) {
    $this->repository->expects($this->once())->method('projectionName')->willReturn($name);

    $this->assertNoEventDispatched(fn () => expect($this->eventRepository->projectionName())->toBe($name));
})->with(['foo', 'bar']);
