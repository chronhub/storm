<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Exceptions;

use Chronhub\Storm\Contracts\Projector\ProjectorFailed;
use Chronhub\Storm\Projector\ProjectionStatus;
use Throwable;

class ProjectionFailed extends RuntimeException implements ProjectorFailed
{
    public static function fromProjectionException(Throwable $exception, string $message = null): self
    {
        return new static(
            $message ?? $exception->getMessage(),
            $exception->getCode(),
            $exception
        );
    }

    public static function failedOnOperation(string $message): self
    {
        return new static($message);
    }

    public static function failedOnCreate(string $streamName): self
    {
        return new static("Unable to create projection for stream name: $streamName");
    }

    public static function failedOnStop(string $streamName): self
    {
        return new static("Unable to stop projection for stream name: $streamName");
    }

    public static function failedOnStartAgain(string $streamName): self
    {
        return new static("Unable to restart projection for stream name: $streamName");
    }

    public static function failedOnPersist(string $streamName): self
    {
        return new static("Unable to persist projection for stream name: $streamName");
    }

    public static function failedOnReset(string $streamName): self
    {
        return new static("Unable to reset projection for stream name: $streamName");
    }

    public static function failedOnDelete(string $streamName): self
    {
        return new static("Unable to delete projection for stream name: $streamName");
    }

    public static function failedOnAcquireLock(string $streamName): ProjectionAlreadyRunning
    {
        $message = "Acquiring lock failed for stream name: $streamName: ";
        $message .= 'another projection process is already running or ';
        $message .= 'wait till the stopping process complete';

        return new ProjectionAlreadyRunning($message);
    }

    public static function failedOnUpdateLock(string $streamName): self
    {
        return new static("Unable to update projection lock for stream name: $streamName");
    }

    public static function failedOnReleaseLock(string $streamName): self
    {
        return new static("Unable to release projection lock for stream name: $streamName");
    }

    public static function failedOnUpdateStatus(string $streamName, ProjectionStatus $status, Throwable $exception): self
    {
        $message = "Unable to update projection status for stream name $streamName and status $status->value";

        return self::fromProjectionException($exception, $message);
    }
}
