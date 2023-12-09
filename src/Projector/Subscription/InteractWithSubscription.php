<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\ContextInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionStateInterface;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Projector\Scheme\Workflow;

trait InteractWithSubscription
{
    public function inner(): Beacon
    {
        return $this->manager;
    }

    public function context(): ContextInterface
    {
        return $this->manager->context();
    }

    public function state(): ProjectionStateInterface
    {
        return $this->manager->state();
    }

    /**
     * @internal
     */
    abstract public function getScope(): ProjectorScope;

    abstract protected function newWorkflow(): Workflow;
}
