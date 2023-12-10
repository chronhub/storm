<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\ContextReaderInterface;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Scheme\Workflow;
use Closure;

use function is_array;

trait InteractWithSubscription
{
    public function initializeAgain(): void
    {
        $this->state->reset();

        $this->setOriginalUserState();
    }

    public function &currentStreamName(): ?string
    {
        return $this->holder->currentStreamName();
    }

    public function setStreamName(string &$streamName): void
    {
        $this->holder->setStreamName($streamName);
    }

    public function currentStatus(): ProjectionStatus
    {
        return $this->holder->currentStatus();
    }

    public function setStatus(ProjectionStatus $status): void
    {
        $this->holder->setStatus($status);
    }

    public function setStreamIterator(MergeStreamIterator $streamIterator): void
    {
        $this->holder->setStreamIterator($streamIterator);
    }

    public function pullStreamIterator(): ?MergeStreamIterator
    {
        return $this->holder->pullStreamIterator();
    }

    public function context(): ContextReaderInterface
    {
        return $this->context;
    }

    public function outputState(): array
    {
        return $this->state->get();
    }

    private function setOriginalUserState(): void
    {
        $callback = $this->context->userState();

        if ($callback instanceof Closure) {
            $userState = $callback();

            if (is_array($userState)) {
                $this->state->put($userState);
            }
        } else {
            $this->state->put([]);
        }
    }

    /**
     * @internal
     */
    abstract public function getScope(): ProjectorScope;

    abstract protected function newWorkflow(): Workflow;
}
