<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Reporter\DomainEvent;
use Closure;

use function is_array;
use function pcntl_signal_dispatch;

final readonly class EventProcessor
{
    public function __construct(private Closure $reactors)
    {
    }

    public function __invoke(Subscription $subscription, DomainEvent $event, int $nextPosition): bool
    {
        if ($subscription->option()->getSignal()) {
            pcntl_signal_dispatch();
        }

        // gap has been detected for persistent subscription
        if (! $this->bindStream($subscription, $event, $nextPosition)) {
            return false;
        }

        if ($subscription instanceof PersistentSubscriptionInterface) {
            $subscription->eventCounter()->increment();
        }

        // handle event and user state if it has been initialized and returned
        $userState = ($this->reactors)($event, $subscription->state()->get());

        if (is_array($userState)) {
            $subscription->state()->put($userState);
        }

        // when block size is reached, persist data
        if ($subscription instanceof PersistentSubscriptionInterface) {
            $subscription->persistWhenCounterIsReached();
        }

        return $subscription->sprint()->inProgress();
    }

    /**
     * Bind the current stream name to the expected position if match
     */
    private function bindStream(Subscription $subscription, DomainEvent $event, int $nextPosition): bool
    {
        // query subscription does not mind of gap
        $eventTime = $subscription instanceof PersistentSubscriptionInterface
            ? $event->header(Header::EVENT_TIME)
            : false;

        return $subscription->streamManager()->bind($subscription->currentStreamName(), $nextPosition, $eventTime);
    }
}
