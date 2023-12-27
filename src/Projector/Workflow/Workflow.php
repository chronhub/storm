<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow;

use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Projector\Exceptions\ProjectionAlreadyRunning;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionFreed;
use Closure;
use Throwable;

use function array_reduce;
use function array_reverse;

final readonly class Workflow
{
    /**
     * @param array<callable> $activities
     */
    public function __construct(private HookHub $hub, private array $activities)
    {
    }

    public function process(Closure $destination): bool
    {
        $process = $this->prepareProcess($destination);

        try {
            return $process($this->hub);
        } catch (Throwable $exception) {
            return false;
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
        return fn (HookHub $hub) => $destination($hub);
    }

    private function carry(): Closure
    {
        return fn (callable $stack, callable $activity) => fn (HookHub $hub) => $activity($hub, $stack);
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
