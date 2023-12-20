<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\ContextReader;
use Chronhub\Storm\Contracts\Projector\QueryManagement;
use Chronhub\Storm\Contracts\Projector\QueryProjectorScope;
use Chronhub\Storm\Contracts\Projector\QuerySubscriber;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Projector\Scope\QueryAccess;
use Chronhub\Storm\Projector\Workflow\RunProjection;
use Chronhub\Storm\Projector\Workflow\Workflow;
use Closure;

final readonly class QuerySubscription implements QuerySubscriber
{
    public function __construct(
        private Subscription $subscription,
        private QueryManagement $management,
    ) {
    }

    public function start(ContextReader $context, bool $keepRunning): void
    {
        $this->initializeContext($context, $keepRunning);

        $this->startProjection($keepRunning);
    }

    public function getState(): array
    {
        return $this->subscription->state->get();
    }

    public function resets(): void
    {
        $this->subscription->streamManager->resets();

        $this->subscription->initializeAgain();
    }

    public function getScope(): QueryProjectorScope
    {
        $userScope = $this->subscription->context()->userScope();

        if ($userScope instanceof Closure) {
            return $userScope($this->subscription);
        }

        return new QueryAccess($this->management);
    }

    protected function newWorkflow(): Workflow
    {
        $activities = ($this->subscription->activityFactory)($this->subscription, $this->getScope(), null);

        return new Workflow($this->subscription, $activities, null);
    }

    private function initializeContext(ContextReader $context, bool $keepRunning): void
    {
        if (! $this->subscription->isContextInitialized()) {
            $this->subscription->setContext($context, true);

            $this->subscription->setOriginalUserState();
        }

        $this->initializeContextAgain();

        $this->subscription->sprint->runInBackground($keepRunning);
        $this->subscription->sprint->continue();
    }

    private function initializeContextAgain(): void
    {
        if ($this->subscription->context()->keepState() === true) {
            if (! $this->subscription->context()->userState() instanceof Closure) {
                throw new RuntimeException('Projection context is not initialized. Provide a closure to initialize user state');
            }
        } else {
            $this->subscription->setOriginalUserState();
        }
    }

    private function startProjection(bool $keepRunning): void
    {
        $project = new RunProjection(
            $this->newWorkflow(),
            $this->subscription->looper,
            $keepRunning
        );

        $project->beginCycle();
    }
}
