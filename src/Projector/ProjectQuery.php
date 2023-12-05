<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Projector\QueryProjector;
use Chronhub\Storm\Contracts\Projector\QuerySubscriptionInterface;
use Chronhub\Storm\Projector\Scheme\QueryProjectorScope;
use Chronhub\Storm\Projector\Scheme\Workflow;
use Closure;

final readonly class ProjectQuery implements QueryProjector
{
    use InteractWithProjection;

    public function __construct(
        protected QuerySubscriptionInterface $subscription,
    ) {
    }

    public function stop(): void
    {
        $this->subscription->sprint()->stop();
    }

    public function reset(): void
    {
        $this->subscription->streamManager()->resets();

        $this->subscription->initializeAgain();
    }

    public function getScope(): QueryProjectorScope
    {
        $userScope = $this->context()->userScope();

        if ($userScope instanceof Closure) {
            return $userScope($this);
        }

        return new QueryProjectorScope(
            $this, $this->subscription->clock(), fn (): string => $this->subscription->currentStreamName()
        );
    }

    protected function newWorkflow(): Workflow
    {
        $activities = ProvideActivities::query($this);

        return new Workflow($this->subscription, $activities);
    }
}
