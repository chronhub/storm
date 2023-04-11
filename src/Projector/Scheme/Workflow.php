<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Projector\ProjectionManagement;
use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\Exceptions\ProjectionAlreadyRunning;
use Closure;
use Throwable;
use function array_reduce;
use function array_reverse;

final class Workflow
{
    /**
     * @var array<callable>
     */
    private array $activities;

    public function __construct(
        private readonly Subscription $subscription,
        private readonly ?ProjectionManagement $repository
    ) {
    }

    public function through(array $activities): self
    {
        $this->activities = $activities;

        return $this;
    }

    public function process(Closure $destination): bool
    {
        $process = array_reduce(
            array_reverse($this->activities),
            $this->carry(),
            $this->prepareDestination($destination)
        );

        return $process($this->subscription, $this->repository);
    }

    private function prepareDestination(Closure $destination): Closure
    {
        return function (Subscription $subscription, ?ProjectionManagement $repository) use ($destination) {
            if (! $subscription->sprint()->inProgress()) {
                $this->tryReleaseLock($repository);
            }

            return $destination($subscription, $repository);
        };
    }

    private function carry(): Closure
    {
        return function ($stack, $activity) {
            return function (Subscription $subscription, ?ProjectionManagement $repository) use ($stack, $activity) {
                try {
                    return $activity($subscription, $repository, $stack);
                } catch (Throwable $exception) {
                    $this->handleException($exception, $repository);
                }
            };
        };
    }

    private function handleException(Throwable $exception, ?ProjectionManagement $repository): void
    {
        if ($exception instanceof ProjectionAlreadyRunning) {
            throw $exception;
        }

        $this->tryReleaseLock($repository);

        throw $exception;
    }

    private function tryReleaseLock(?ProjectionManagement $repository): void
    {
        try {
            $repository?->freed();
        } catch (Throwable) {
            // failed silently
        }
    }
}
