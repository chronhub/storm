<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\ContextReader;
use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryFilter;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Projector\Workflow\RunProjection;
use Chronhub\Storm\Projector\Workflow\Workflow;

trait InteractWithPersistentSubscription
{
    public function start(ContextReader $context, bool $keepRunning): void
    {
        $this->initializeContext($context);

        $this->setupWatcher($context, $keepRunning);

        $this->startProjection($keepRunning);
    }

    public function hub(): NotificationHub
    {
        return $this->management->hub();
    }

    private function newWorkflow(): Workflow
    {
        $activities = ($this->activities)($this->subscriptor, $this->scope);

        return new Workflow($this->hub(), $activities);
    }

    private function initializeContext(ContextReader $context): void
    {
        $this->validateContext($context);

        $this->subscriptor->setContext($context, true);
        $this->subscriptor->restoreUserState();
    }

    private function setupWatcher(ContextReader $context, bool $keepRunning): void
    {
        $this->subscriptor->watcher()->stopWhen()->subscribe($this->hub(), $context->haltOnCallback());
        $this->subscriptor->watcher()->sprint()->runInBackground($keepRunning);
        $this->subscriptor->watcher()->sprint()->continue();
    }

    private function startProjection(bool $keepRunning): void
    {
        $projection = new RunProjection($this->newWorkflow(), $keepRunning);

        $projection->loop();
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
