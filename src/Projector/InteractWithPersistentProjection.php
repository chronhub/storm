<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Projector\Scheme\Workflow;

trait InteractWithPersistentProjection
{
    public function stop(): void
    {
        $this->subscription->close();
    }

    public function reset(): void
    {
        $this->subscription->revise();
    }

    public function delete(bool $withEmittedEvents): void
    {
        $this->subscription->discard($withEmittedEvents);
    }

    public function getName(): string
    {
        return $this->subscription->getName();
    }

    protected function newWorkflow(): Workflow
    {
        $activities = ProvideActivities::persistent($this);

        return new Workflow($this->subscription, $activities);
    }
}
