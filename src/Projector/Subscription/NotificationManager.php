<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\HookHub;
use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Projector\Subscription\Hook\EventEmitted;
use Chronhub\Storm\Projector\Subscription\Hook\EventLinkedTo;
use Chronhub\Storm\Projector\Subscription\Hook\ProjectionClosed;
use Chronhub\Storm\Projector\Subscription\Hook\ProjectionDiscarded;
use Chronhub\Storm\Projector\Subscription\Hook\ProjectionFreed;
use Chronhub\Storm\Projector\Subscription\Hook\ProjectionLockUpdated;
use Chronhub\Storm\Projector\Subscription\Hook\ProjectionPersistedWhenThresholdIsReached;
use Chronhub\Storm\Projector\Subscription\Hook\ProjectionRestarted;
use Chronhub\Storm\Projector\Subscription\Hook\ProjectionRevised;
use Chronhub\Storm\Projector\Subscription\Hook\ProjectionRise;
use Chronhub\Storm\Projector\Subscription\Hook\ProjectionStatusDisclosed;
use Chronhub\Storm\Projector\Subscription\Hook\ProjectionStored;
use Chronhub\Storm\Projector\Subscription\Hook\ProjectionSynchronized;

use function array_key_exists;
use function is_object;
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

    /**
     * @var array<string, array<string|callable>>
     */
    private array $listeners = [];

    public function __construct(private readonly Subscriptor $subscriptor)
    {
    }

    public function addHook(string $hook, callable $trigger): void
    {
        $this->assertHookIsSupported($hook);

        $this->hooks[$hook][] = $trigger;
    }

    public function addHooks(array $hooks): void
    {
        foreach ($hooks as $hook => $trigger) {
            $this->addHook($hook, $trigger);
        }
    }

    public function trigger(object $hook): void
    {
        $hookClassName = $hook::class;

        $this->assertHookIsSupported($hookClassName);

        foreach ($this->hooks[$hookClassName] as $trigger) {
            $trigger($hook);
        }
    }

    public function addListener(string $listener, string|callable $callback): void
    {
        $this->listeners[$listener][] = $callback;
    }

    public function expect(string|object $notification, mixed ...$arguments): mixed
    {
        $event = $this->makeEvent($notification, ...$arguments);

        $result = $this->subscriptor->receive($event);

        $this->handleListener($event, $result);

        return $result;
    }

    public function notify(string|object $notification, mixed ...$arguments): void
    {
        $event = $this->makeEvent($notification, ...$arguments);

        $this->subscriptor->receive($event);

        $this->handleListener($event, null);
    }

    private function handleListener(object $event, mixed $result): void
    {
        if (array_key_exists($event::class, $this->listeners)) {
            foreach ($this->listeners[$event::class] as &$listener) {
                if (is_string($listener)) {
                    $listener = new $listener();
                }

                $listener($this, $event, $result);
            }
        }
    }

    private function makeEvent(string|object $notification, mixed ...$arguments): object
    {
        if (is_object($notification)) {
            return $notification;
        }

        return new $notification(...$arguments);
    }

    private function assertHookIsSupported(string $hookClassName): void
    {
        if (! array_key_exists($hookClassName, $this->hooks)) {
            throw new InvalidArgumentException("Hook $hookClassName is not supported");
        }
    }
}
