<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\ContextReader;
use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Projector\Support\Notification\Sprint\IsSprintTerminated;
use Chronhub\Storm\Projector\Workflow\Workflow;
use Closure;

trait InteractWithPersistentSubscription
{
    public function start(ContextReader $context, bool $keepRunning): void
    {
        $this->initializeContext($context);

        $this->setupWatcher($context, $keepRunning);

        $this->startProjection();
    }

    public function interact(Closure $callback): mixed
    {
        return value($callback, $this->management->hub());
    }

    private function startProjection(): void
    {
        $activities = ($this->activities)($this->subscriptor, $this->scope);

        $workflow = new Workflow($this->management->hub(), $activities);

        $workflow->process(fn (NotificationHub $hub): bool => $hub->expect(IsSprintTerminated::class));
    }

    private function initializeContext(ContextReader $context): void
    {
        $this->validateContext($context);

        $this->subscriptor->setContext($context, true);
        $this->subscriptor->restoreUserState();
    }

    private function setupWatcher(ContextReader $context, bool $keepRunning): void
    {
        $this->subscriptor->watcher()->subscribe($this->management->hub(), $context);
        $this->subscriptor->watcher()->sprint()->runInBackground($keepRunning);
        $this->subscriptor->watcher()->sprint()->continue();
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
