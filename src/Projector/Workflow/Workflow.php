<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Exceptions\ProjectionAlreadyRunning;
use Chronhub\Storm\Projector\Subscription\Management\ProjectionFreed;
use Closure;
use Throwable;

use function array_reduce;
use function array_reverse;

class Workflow
{
    /**
     * @param array<callable> $activities
     */
    public function __construct(
        protected readonly NotificationHub $hub,
        protected readonly array $activities
    ) {
    }

    public function process(Closure $destination): void
    {
        $process = $this->prepareProcess($destination);

        try {
            do {
                $inProgress = $process($this->hub);
            } while ($inProgress);
        } catch (Throwable $exception) {
            //
        } finally {
            $this->conditionallyReleaseLock($exception ?? null);
        }
    }

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
        return fn (NotificationHub $hub) => $destination($hub);
    }

    private function carry(): Closure
    {
        return fn (callable $stack, callable $activity) => fn (NotificationHub $hub) => $activity($hub, $stack);
    }

    private function conditionallyReleaseLock(?Throwable $exception): void
    {
        if ($exception instanceof ProjectionAlreadyRunning) {
            throw $exception;
        }

        try {
            $this->hub->trigger(new ProjectionFreed());
        } catch (Throwable) {
            // ignore
        }

        if ($exception) {
            throw $exception;
        }
    }
}
