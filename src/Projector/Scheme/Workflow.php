<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
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

    public function __construct(private readonly Subscription $subscription)
    {
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

        return $process($this->subscription);
    }

    private function prepareDestination(Closure $destination): Closure
    {
        return function (Subscription $subscription) use ($destination) {
            if (! $subscription->sprint()->inProgress()) {
                $this->tryReleaseLock($subscription);
            }

            return $destination($subscription);
        };
    }

    private function carry(): Closure
    {
        return function ($stack, $activity) {
            return function (Subscription $subscription) use ($stack, $activity) {
                try {
                    return $activity($subscription, $stack);
                } catch (Throwable $exception) {
                    $this->handleException($exception, $subscription);
                }
            };
        };
    }

    private function handleException(Throwable $exception, Subscription $subscription): void
    {
        if ($exception instanceof ProjectionAlreadyRunning) {
            throw $exception;
        }

        $this->tryReleaseLock($subscription);

        throw $exception;
    }

    private function tryReleaseLock(Subscription $subscription): void
    {
        if ($subscription instanceof PersistentSubscriptionInterface) {
            try {
                $subscription->freed();
            } catch (Throwable) {
                // failed silently
            }
        }
    }
}
