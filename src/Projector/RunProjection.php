<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Projector\ProjectorRepository;
use Chronhub\Storm\Projector\Exceptions\ProjectionAlreadyRunning;
use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Projector\Scheme\Pipeline;
use Throwable;

readonly class RunProjection
{
    public function __construct(private array $pipes,
                                private ?ProjectorRepository $repository)
    {
    }

    public function __invoke(Context $context): void
    {
        $pipeline = (new Pipeline())->through($this->pipes);

        $quit = $this->runProjection($pipeline, $context);

        $this->tryReleaseLock($quit);
    }

    /**
     * Run Projection
     *
     * @param  Pipeline  $pipeline
     * @param  Context  $context
     * @return null|Throwable
     */
    protected function runProjection(Pipeline $pipeline, Context $context): ?Throwable
    {
        try {
            $exception = null;

            do {
                $isStopped = $pipeline
                    ->send($context)
                    ->then(static fn (Context $context): bool => $context->runner->isStopped());
            } while ($context->runner->inBackground() && ! $isStopped);
        } catch (Throwable $e) {
            $exception = $e;
        } finally {
            return $exception;
        }
    }

    /**
     * Try release lock
     *
     * if an error occurred releasing lock, we just failed silently
     * and raise the original exception if exists
     *
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
        }

        if ($exception) {
            throw $exception;
        }
    }
}
