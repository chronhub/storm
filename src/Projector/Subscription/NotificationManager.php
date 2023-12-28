<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Projector\Subscription\Engagement\EventEmitted;
use Chronhub\Storm\Projector\Subscription\Engagement\EventLinkedTo;
use Chronhub\Storm\Projector\Subscription\Engagement\ProjectionClosed;
use Chronhub\Storm\Projector\Subscription\Engagement\ProjectionDiscarded;
use Chronhub\Storm\Projector\Subscription\Engagement\ProjectionFreed;
use Chronhub\Storm\Projector\Subscription\Engagement\ProjectionLockUpdated;
use Chronhub\Storm\Projector\Subscription\Engagement\ProjectionPersistedWhenThresholdIsReached;
use Chronhub\Storm\Projector\Subscription\Engagement\ProjectionRestarted;
use Chronhub\Storm\Projector\Subscription\Engagement\ProjectionRevised;
use Chronhub\Storm\Projector\Subscription\Engagement\ProjectionRise;
use Chronhub\Storm\Projector\Subscription\Engagement\ProjectionStatusDisclosed;
use Chronhub\Storm\Projector\Subscription\Engagement\ProjectionStored;
use Chronhub\Storm\Projector\Subscription\Engagement\ProjectionSynchronized;

use function array_key_exists;
use function is_string;

final class NotificationManager implements HookHub
{
    /**
     * @var array<string, array<callable>>
     */
    private array $hooks = [
        ProjectionRise::class => [],
        ProjectionStored::class => [],
        ProjectionClosed::class => [],
        ProjectionRevised::class => [],
        ProjectionDiscarded::class => [],
        ProjectionFreed::class => [],
        ProjectionRestarted::class => [],
        ProjectionLockUpdated::class => [],
        ProjectionSynchronized::class => [],
        ProjectionStatusDisclosed::class => [],
        ProjectionPersistedWhenThresholdIsReached::class => [],
        EventEmitted::class => [],
        EventLinkedTo::class => [],
    ];

    public function __construct(private readonly Subscriptor $subscriptor)
    {
    }

    public function addHook(string $hook, callable $trigger): void
    {
        $this->assertHookIsSupported($hook);

        $this->hooks[$hook][] = $trigger;
    }

    public function trigger(object $hook): void
    {
        $hookClass = $hook::class;

        $this->assertHookIsSupported($hookClass);

        foreach ($this->hooks[$hookClass] as $trigger) {
            $trigger($hook);
        }
    }

    public function interact(string|object $notification, mixed ...$arguments): mixed
    {
        $event = is_string($notification) ? new $notification(...$arguments) : $notification;

        return $this->subscriptor->receive($event);
    }

    public function __call(string $method, array $arguments): mixed
    {
        return $this->subscriptor->{$method}(...$arguments);
    }

    private function assertHookIsSupported(string $hook): void
    {
        if (! array_key_exists($hook, $this->hooks)) {
            throw new InvalidArgumentException("Hook $hook is not supported");
        }
    }
}
