<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Projector\PersistentSubscriptionInterface;
use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Reporter\DomainEvent;
use Closure;
use DateTimeImmutable;

use function is_string;
use function pcntl_signal_dispatch;

final readonly class EventProcessor
{
    public function __construct(private Closure $reactors)
    {
    }

    public function __invoke(Subscription $subscription, DomainEvent $event, int $position): bool
    {
        if (! $this->preProcess($subscription, $event, $position)) {
            return false;
        }

        $userState = ($this->reactors)($event, $subscription->state()->get());

        return $this->afterProcess($subscription, $userState);
    }

    /**
     * Bind stream name to his position when no gap detected and no more retry left
     * It will also increment event counter for persistent subscription
     */
    private function preProcess(Subscription $subscription, DomainEvent $event, int $position): bool
    {
        if ($subscription->option()->getSignal()) {
            pcntl_signal_dispatch();
        }

        $streamName = $subscription->currentStreamName();

        $eventTime = $this->getEventTime($subscription, $event);

        $isBound = $subscription->streamManager()->bind($streamName, $position, $eventTime);

        if (! $isBound) {
            return false;
        }

        if ($subscription instanceof PersistentSubscriptionInterface) {
            $subscription->eventCounter()->increment();
        }

        return true;
    }

    private function afterProcess(Subscription $subscription, ?array $userState): bool
    {
        if ($userState) {
            $subscription->state()->put($userState);
        }

        if ($subscription instanceof PersistentSubscriptionInterface) {
            $subscription->persistWhenCounterIsReached();
        }

        return $subscription->sprint()->inProgress();
    }

    /**
     * @throws InvalidArgumentException when event time header is not a string or DateTimeImmutable
     */
    private function getEventTime(Subscription $subscription, DomainEvent $event): DateTimeImmutable|string|false
    {
        if (! $subscription instanceof PersistentSubscriptionInterface) {
            return false;
        }

        $eventTime = $event->header(Header::EVENT_TIME);

        if (! is_string($eventTime) && ! $eventTime instanceof DateTimeImmutable) {
            throw new InvalidArgumentException("Event time header not found in event {$event->header(Header::EVENT_TYPE)}");
        }

        return $eventTime;
    }
}
