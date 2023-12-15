<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Projector\PersistentManagement;
use Chronhub\Storm\Projector\Exceptions\ProjectionAlreadyRunning;
use Chronhub\Storm\Projector\Subscription\Subscription;
use Closure;
use Throwable;

use function array_reduce;
use function array_reverse;

final readonly class Workflow
{
    /**
     * @param array<callable> $activities
     */
    public function __construct(
        private Subscription $subscription,
        private array $activities,
        private ?PersistentManagement $management,
    ) {
    }

    public function process(Closure $destination): bool
    {
        $process = $this->prepareProcess($destination);

        try {
            return $process($this->subscription);
        } catch (Throwable $exception) {
            return false;
        } finally {
            $this->raiseExceptionAndReleaseLock($exception ?? null);
        }
    }

    // todo reimplement then and thenReturn bool
    // we could have stuff to do in then

    private function prepareProcess(Closure $destination): Closure
    {
        return array_reduce(
            array_reverse($this->activities),
            $this->carry(),
            $this->prepareDestination($destination)
        );
    }

    private function prepareDestination(Closure $destination): Closure
    {
        return fn (Subscription $subscription) => $destination($subscription);
    }

    private function carry(): Closure
    {
        return fn (callable $stack, callable $activity) => fn (Subscription $subscription) => $activity($subscription, $stack);
    }

    private function raiseExceptionAndReleaseLock(?Throwable $exception): void
    {
        // raise projection already running exception, prevent from releasing lock
        // and put the projection in a idle status.
        if (! $this->management || $exception instanceof ProjectionAlreadyRunning) {
            throw $exception;
        }

        try {
            $this->management->freed();
        } catch (Throwable) {
            // ignore
        }

        if ($exception) {
            throw $exception;
        }
    }
}
