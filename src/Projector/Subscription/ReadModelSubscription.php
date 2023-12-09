<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Chronicler\ChroniclerDecorator;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\ContextReaderInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\ProjectionRepositoryInterface;
use Chronhub\Storm\Contracts\Projector\ProjectionStateInterface;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Contracts\Projector\ReadModelProjectorScopeInterface;
use Chronhub\Storm\Contracts\Projector\ReadModelSubscriber;
use Chronhub\Storm\Contracts\Projector\StreamManagerInterface;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Projector\Scheme\ProjectionState;
use Chronhub\Storm\Projector\Scheme\ReadModelProjectorScope;
use Chronhub\Storm\Projector\Scheme\Sprint;
use Closure;

final readonly class ReadModelSubscription implements ReadModelSubscriber
{
    use InteractWithPersistentSubscription;
    use InteractWithSubscription;

    public Sprint $sprint;

    public Chronicler $chronicler;

    public ProjectionStateInterface $state;

    public ReadModelManagement $management;

    protected SubscriptionHolder $holder;

    public function __construct(
        public ContextReaderInterface $context,
        public StreamManagerInterface $streamBinder,
        public SystemClock $clock,
        public ProjectionOption $option,
        Chronicler $chronicler,
        ProjectionRepositoryInterface $repository,
        public EventCounter $eventCounter,
        private ReadModel $readModel,
    ) {
        while ($chronicler instanceof ChroniclerDecorator) {
            $chronicler = $chronicler->innerChronicler();
        }

        $this->chronicler = $chronicler;
        $this->state = new ProjectionState();
        $this->sprint = new Sprint();
        $this->management = new ReadModelManagement($this, $repository);
        $this->holder = new SubscriptionHolder();
    }

    public function readModel(): ReadModel
    {
        return $this->readModel;
    }

    public function getScope(): ReadModelProjectorScopeInterface
    {
        $userScope = $this->context->userScope();

        if ($userScope instanceof Closure) {
            return $userScope($this);
        }

        return new ReadModelProjectorScope($this->management, $this);
    }
}
