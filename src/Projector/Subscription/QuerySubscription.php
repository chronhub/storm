<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\ActivityFactory;
use Chronhub\Storm\Contracts\Projector\ContextReader;
use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Contracts\Projector\QueryManagement;
use Chronhub\Storm\Contracts\Projector\QueryProjectorScope;
use Chronhub\Storm\Contracts\Projector\QuerySubscriber;
use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Projector\Workflow\RunProjection;
use Chronhub\Storm\Projector\Workflow\Workflow;

final readonly class QuerySubscription implements QuerySubscriber
{
    public function __construct(
        private Subscriptor $subscriptor,
        private QueryManagement $management,
        private ActivityFactory $activities,
        private QueryProjectorScope $scope
    ) {
    }

    public function start(ContextReader $context, bool $keepRunning): void
    {
        $this->initializeContext($context, $keepRunning);

        $this->startProjection($keepRunning);
    }

    public function resets(): void
    {
        $this->subscriptor->recognition()->resets();

        $this->subscriptor->restoreUserState();
    }

    public function hub(): HookHub
    {
        return $this->management->hub();
    }

    private function newWorkflow(): Workflow
    {
        $activities = ($this->activities)($this->subscriptor, $this->scope, $this->management);

        return new Workflow($this->hub(), $activities);
    }

    private function initializeContext(ContextReader $context, bool $keepRunning): void
    {
        if ($this->subscriptor->getContext() === null) {
            $this->subscriptor->setContext($context, true);

            $this->subscriptor->restoreUserState();
        }

        $this->initializeContextAgain();

        $this->subscriptor->monitor()->sprint()->runInBackground($keepRunning);
        $this->subscriptor->monitor()->sprint()->continue();
    }

    private function initializeContextAgain(): void
    {
        if ($this->subscriptor->getContext()->keepState() === true) {
            if (! $this->subscriptor->isUserStateInitialized()) {
                throw new RuntimeException('Projection context is not initialized. Provide a closure to initialize user state');
            }
        } else {
            $this->subscriptor->restoreUserState();
        }
    }

    private function startProjection(bool $keepRunning): void
    {
        $project = new RunProjection($this->newWorkflow(), $keepRunning);

        $project->loop();
    }
}
