<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\QuerySubscriptionInterface;
use Chronhub\Storm\Projector\ProvideActivities;
use Chronhub\Storm\Projector\Scheme\QueryProjectorScope;
use Chronhub\Storm\Projector\Scheme\RunProjection;
use Chronhub\Storm\Projector\Scheme\Workflow;
use Closure;

final class QuerySubscription implements QuerySubscriptionInterface
{
    use InteractWithSubscription;

    public function __construct(protected readonly GenericSubscription $subscription)
    {
    }

    public function start(bool $keepRunning): void
    {
        $this->subscription->start($keepRunning);

        $project = new RunProjection($this, $this->newWorkflow());

        $project->beginCycle();
    }

    public function getScope(): QueryProjectorScope
    {
        $userScope = $this->context()->userScope();

        if ($userScope instanceof Closure) {
            return $userScope($this);
        }

        return new QueryProjectorScope($this);
    }

    protected function newWorkflow(): Workflow
    {
        $activities = ProvideActivities::forQuery($this);

        return new Workflow($this, $activities);
    }
}
