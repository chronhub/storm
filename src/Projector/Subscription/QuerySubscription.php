<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\ActivityFactory;
use Chronhub\Storm\Contracts\Projector\ContextReader;
use Chronhub\Storm\Contracts\Projector\QueryManagement;
use Chronhub\Storm\Contracts\Projector\QueryProjectorScope;
use Chronhub\Storm\Contracts\Projector\QuerySubscriber;
use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Projector\Scope\QueryAccess;
use Chronhub\Storm\Projector\Workflow\RunProjection;
use Chronhub\Storm\Projector\Workflow\Workflow;

final readonly class QuerySubscription implements QuerySubscriber
{
    public function __construct(
        private Subscriptor $subscriptor,
        private QueryManagement $management,
        private ActivityFactory $activities
    ) {
    }

    public function start(ContextReader $context, bool $keepRunning): void
    {
        $this->initializeContext($context, $keepRunning);

        $this->startProjection($keepRunning);
    }

    public function resets(): void
    {
        $this->subscriptor->resetCheckpoints();

        $this->subscriptor->setOriginalUserState();
    }

    public function notify(): Notification
    {
        return $this->management->notify();
    }

    protected function getScope(): QueryProjectorScope
    {
        return new QueryAccess($this->notify(), $this->subscriptor->clock());
    }

    protected function newWorkflow(): Workflow
    {
        $activities = ($this->activities)($this->subscriptor, $this->getScope(), $this->management);

        return new Workflow($this->notify(), $activities);
    }

    private function initializeContext(ContextReader $context, bool $keepRunning): void
    {
        if ($this->subscriptor->getContext() === null) {
            $this->subscriptor->setContext($context, true);

            $this->subscriptor->setOriginalUserState();
        }

        $this->initializeContextAgain();

        $this->subscriptor->runInBackground($keepRunning);
        $this->subscriptor->continue();
    }

    private function initializeContextAgain(): void
    {
        if ($this->subscriptor->getContext()->keepState() === true) {
            if (! $this->subscriptor->isUserStateInitialized()) {
                throw new RuntimeException('Projection context is not initialized. Provide a closure to initialize user state');
            }
        } else {
            $this->subscriptor->setOriginalUserState();
        }
    }

    private function startProjection(bool $keepRunning): void
    {
        $project = new RunProjection($this->newWorkflow(), $this->subscriptor->loop(), $keepRunning);

        $project->loop();
    }
}
