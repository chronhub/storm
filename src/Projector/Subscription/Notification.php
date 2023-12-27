<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Projector\Subscription\Observer\EventEmitted;
use Chronhub\Storm\Projector\Subscription\Observer\EventLinkedTo;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionClosed;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionDiscarded;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionFreed;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionLockUpdated;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionPersistedWhenThresholdIsReached;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionRestarted;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionRevised;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionRise;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionStatusDisclosed;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionStored;
use Chronhub\Storm\Projector\Subscription\Observer\ProjectionSynchronized;

use function array_key_exists;
use function is_string;

final class Notification implements HookHub
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

    public function listen(string|object $notification, mixed ...$arguments): mixed
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
            throw new RuntimeException("Hook $hook is not supported");
        }
    }
}
