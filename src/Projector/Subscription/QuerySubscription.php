<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\ActivityFactory;
use Chronhub\Storm\Contracts\Projector\ContextReader;
use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Contracts\Projector\QueryManagement;
use Chronhub\Storm\Contracts\Projector\QueryProjectorScope;
use Chronhub\Storm\Contracts\Projector\QuerySubscriber;
use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Projector\Support\Notification\Sprint\IsSprintTerminated;
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
        $this->initializeContext($context);

        $this->setupWatcher($context, $keepRunning);

        $this->startProjection();
    }

    public function resets(): void
    {
        $this->subscriptor->recognition()->resets();

        $this->subscriptor->restoreUserState();
    }

    public function hub(): NotificationHub
    {
        return $this->management->hub();
    }

    private function startProjection(): void
    {
        $activities = ($this->activities)($this->subscriptor, $this->scope);

        $workflow = new Workflow($this->hub(), $activities);

        $workflow->process(fn (NotificationHub $hub): bool => $hub->expect(IsSprintTerminated::class));
    }

    private function initializeContext(ContextReader $context): void
    {
        if ($this->subscriptor->getContext() === null) {
            $this->subscriptor->setContext($context, true);

            $this->subscriptor->restoreUserState();
        }

        $this->initializeContextAgain();
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

    private function setupWatcher(ContextReader $context, bool $keepRunning): void
    {
        $this->subscriptor->watcher()->stopWhen()->subscribe($this->hub(), $context->haltOnCallback());
        $this->subscriptor->watcher()->sprint()->runInBackground($keepRunning);
        $this->subscriptor->watcher()->sprint()->continue();
    }
}
