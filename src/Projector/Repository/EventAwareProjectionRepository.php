<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Repository;

use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Repository\Event\ProjectionDeleted;
use Chronhub\Storm\Projector\Repository\Event\ProjectionDeletedWithEvents;
use Chronhub\Storm\Projector\Repository\Event\ProjectionError;
use Chronhub\Storm\Projector\Repository\Event\ProjectionReleased;
use Chronhub\Storm\Projector\Repository\Event\ProjectionReset;
use Chronhub\Storm\Projector\Repository\Event\ProjectionRestarted;
use Chronhub\Storm\Projector\Repository\Event\ProjectionStarted;
use Chronhub\Storm\Projector\Repository\Event\ProjectionStopped;
use DateTimeImmutable;
use Illuminate\Contracts\Events\Dispatcher;
use Throwable;

final readonly class EventAwareProjectionRepository implements ProjectionRepositoryInterface
{
    public function __construct(
        private ProjectionRepositoryInterface $projectionRepository,
        private Dispatcher $eventDispatcher
    ) {
    }

    public function create(ProjectionStatus $status): void
    {
        $this->projectionRepository->create($status);
    }

    public function start(): void
    {
        $this->wrapErrorHandling(
            fn () => $this->projectionRepository->start(),
            fn () => $this->eventDispatcher->dispatch(new ProjectionStarted($this->projectionName())),
            ProjectionRestarted::class
        );
    }

    public function persist(ProjectionDetail $projectionDetail, ProjectionStatus $currentStatus): void
    {
        $this->projectionRepository->persist($projectionDetail, $currentStatus);
    }

    public function stop(ProjectionDetail $projectionDetail): void
    {
        $this->wrapErrorHandling(
            fn () => $this->projectionRepository->stop($projectionDetail),
            fn () => $this->eventDispatcher->dispatch(new ProjectionStopped($this->projectionName())),
            ProjectionStopped::class
        );
    }

    public function startAgain(): void
    {
        $this->wrapErrorHandling(
            fn () => $this->projectionRepository->startAgain(),
            fn () => $this->eventDispatcher->dispatch(new ProjectionRestarted($this->projectionName())),
            ProjectionRestarted::class
        );
    }

    public function reset(ProjectionDetail $projectionDetail, ProjectionStatus $currentStatus): void
    {
        $this->wrapErrorHandling(
            fn () => $this->projectionRepository->reset($projectionDetail, $currentStatus),
            fn () => $this->eventDispatcher->dispatch(new ProjectionReset($this->projectionName(), $projectionDetail)),
            ProjectionReset::class
        );
    }

    public function delete(bool $withEmittedEvents): void
    {
        $event = $withEmittedEvents ? ProjectionDeletedWithEvents::class : ProjectionDeleted::class;

        $this->wrapErrorHandling(
            fn () => $this->projectionRepository->delete($withEmittedEvents),
            fn () => $this->eventDispatcher->dispatch(new $event($this->projectionName())),
            $event
        );
    }

    public function release(): void
    {
        $this->wrapErrorHandling(
            fn () => $this->projectionRepository->release(),
            fn () => $this->eventDispatcher->dispatch(new ProjectionReleased($this->projectionName())),
            ProjectionReleased::class
        );
    }

    public function update(ProjectionDetail $projectionDetail, DateTimeImmutable $currentTime): void
    {
        $this->projectionRepository->update($projectionDetail, $currentTime);
    }

    public function canUpdate(DateTimeImmutable $currentTime): bool
    {
        return $this->projectionRepository->canUpdate($currentTime);
    }

    public function loadStatus(): ProjectionStatus
    {
        return $this->projectionRepository->loadStatus();
    }

    public function loadDetail(): ProjectionDetail
    {
        return $this->projectionRepository->loadDetail();
    }

    public function exists(): bool
    {
        return $this->projectionRepository->exists();
    }

    public function projectionName(): string
    {
        return $this->projectionRepository->projectionName();
    }

    private function wrapErrorHandling(callable $operation, callable $onSuccess, string $failedEvent): void
    {
        try {
            $operation();

            $onSuccess();
        } catch (Throwable $exception) {
            $this->eventDispatcher->dispatch(new ProjectionError($this->projectionName(), $failedEvent, $exception));

            throw $exception;
        }
    }
}
