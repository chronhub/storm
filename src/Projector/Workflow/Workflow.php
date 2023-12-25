<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow;

use Chronhub\Storm\Contracts\Projector\PersistentManagement;
use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\Exceptions\ProjectionAlreadyRunning;
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
        private Subscriptor $subscriptor,
        private array $activities,
        private ?PersistentManagement $management,
    ) {
    }

    public function process(Closure $destination): bool
    {
        $process = $this->prepareProcess($destination);

        try {
            return $process($this->subscriptor);
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
        return fn (Subscriptor $subscriptor) => $destination($subscriptor);
    }

    private function carry(): Closure
    {
        return fn (callable $stack, callable $activity) => fn (Subscriptor $subscriptor) => $activity($subscriptor, $stack);
    }

    private function conditionallyReleaseLock(?Throwable $exception): void
    {
        if ($exception instanceof ProjectionAlreadyRunning) {
            throw $exception;
        }

        try {
            $this->management?->freed();
        } catch (Throwable) {
            // ignore
        }

        if ($exception) {
            throw $exception;
        }
    }
}
