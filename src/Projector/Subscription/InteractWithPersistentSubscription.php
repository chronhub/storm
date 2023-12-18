<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\ContextReader;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Projector\Workflow\RunProjection;
use Chronhub\Storm\Projector\Workflow\Workflow;

trait InteractWithPersistentSubscription
{
    public function start(ContextReader $context, bool $keepRunning): void
    {
        $this->initializeContext($context, $keepRunning);

        $this->startProjection($keepRunning);
    }

    public function getName(): string
    {
        return $this->management->getName();
    }

    public function getState(): array
    {
        return $this->subscription->state->get();
    }

    protected function newWorkflow(): Workflow
    {
        $activities = ($this->subscription->activityFactory)($this->subscription, $this->getScope(), $this->management);

        return new Workflow($this->subscription, $activities, $this->management);
    }

    /**
     * @internal
     */
    abstract public function getScope(): ProjectorScope;

    private function initializeContext(ContextReader $context, bool $keepRunning): void
    {
        $this->validateContext($context);

        $this->subscription->setContext($context, true);
        $this->subscription->setOriginalUserState();
        $this->subscription->sprint->runInBackground($keepRunning);
        $this->subscription->sprint->continue();
    }

    private function startProjection(bool $keepRunning): void
    {
        $project = new RunProjection($this->newWorkflow(), $this->subscription->looper, $keepRunning);

        $project->beginCycle();
    }

    private function validateContext(ContextReader $context): void
    {
        if (! $context->queryFilter() instanceof ProjectionQueryFilter) {
            throw new RuntimeException('Persistent subscription requires a projection query filter.');
        }

        if ($context->keepState() === true) {
            throw new RuntimeException('Keep state is only available for query projection.');
        }
    }
}
