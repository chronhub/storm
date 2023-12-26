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

    public function notify(): Notification
    {
        return $this->management->notify();
    }

    protected function newWorkflow(): Workflow
    {
        $activities = ($this->activities)($this->subscriptor, $this->getScope(), $this->management);

        return new Workflow($this->notify(), $activities);
    }

    /**
     * @internal
     */
    abstract protected function getScope(): ProjectorScope;

    private function initializeContext(ContextReader $context, bool $keepRunning): void
    {
        $this->validateContext($context);

        $this->subscriptor->setContext($context, true);
        $this->subscriptor->setOriginalUserState();
        $this->subscriptor->runInBackground($keepRunning);
        $this->subscriptor->continue();
    }

    private function startProjection(bool $keepRunning): void
    {
        $project = new RunProjection($this->newWorkflow(), $this->subscriptor->loop(), $keepRunning);

        $project->loop();
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
