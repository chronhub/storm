<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Throwable;
use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Projector\Scheme\Pipeline;
use Chronhub\Storm\Contracts\Projector\ProjectorRepository;
use Chronhub\Storm\Projector\Exceptions\ProjectionAlreadyRunning;

readonly class RunProjection
{
    public function __construct(private array $pipes,
                                private ?ProjectorRepository $repository)
    {
    }

    public function __invoke(Context $context): void
    {
        $pipeline = (new Pipeline())->through($this->pipes);

        try {
            $quit = null;
            $this->runProjection($pipeline, $context);
        } catch (Throwable $exception) {
            $quit = $exception;
        } finally {
            $this->tryReleaseLock($quit);
        }
    }

    /**
     * Run Projection
     */
    protected function runProjection(Pipeline $pipeline, Context $context): void
    {
        do {
            $isStopped = $pipeline
                ->send($context)
                ->then(static fn (Context $context): bool => $context->runner->isStopped());
        } while ($context->runner->inBackground() && ! $isStopped);
    }

    /**
     * Try release lock
     *
     * if an error occurred releasing lock, we just failed silently
     * and raise the original exception if exists
     *
     * @throws ProjectionAlreadyRunning
     * @throws Throwable
     */
    protected function tryReleaseLock(?Throwable $exception): void
    {
        if ($exception instanceof ProjectionAlreadyRunning) {
            throw $exception;
        }

        try {
            $this->repository?->freed();
        } catch (Throwable) {
            // failed silently
        }

        if ($exception) {
            throw $exception;
        }
    }
}
