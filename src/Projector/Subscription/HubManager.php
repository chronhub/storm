<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
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
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

use function array_key_exists;
use function is_callable;
use function is_object;
use function is_string;

final class HubManager implements NotificationHub
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
     * @var Collection<string, array<string|callable>>
     */
    private Collection $listeners;

    public function __construct(private readonly Subscriptor $subscriptor)
    {
        $this->listeners = new Collection();
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

    public function addListener(string $event, string|callable|array $callback): void
    {
        $callbacks = Arr::wrap($callback);

        $this->listeners = ! $this->listeners->has($event)
            ? $this->listeners->put($event, $callbacks)
            : $this->listeners->mergeRecursive([$event => $callback]);
    }

    public function addListeners(array $listeners): void
    {
        foreach ($listeners as $event => $callback) {
            $this->addListener($event, $callback);
        }
    }

    public function forgetListener(string $event): void
    {
        $this->listeners = $this->listeners->forget($event);
    }

    public function expect(string|object $event, mixed ...$arguments): mixed
    {
        $notification = $this->makeEvent($event, ...$arguments);

        $result = $this->subscriptor->capture($notification);

        $this->handleListener($notification, $result);

        return $result;
    }

    public function notify(string|object $event, mixed ...$arguments): void
    {
        $notification = $this->makeEvent($event, ...$arguments);

        // when the event provided is not callable, it means that it is a notification object,
        // so we just pass it to his handlers
        $result = is_callable($notification) ? $this->subscriptor->capture($notification) : null;

        $this->handleListener($notification, $result);
    }

    private function handleListener(object $event, mixed $result): void
    {
        foreach ($this->listeners->get($event::class, []) as $handler) {
            if (is_string($handler)) {
                $handler = new $handler();
            }

            if (! is_callable($handler)) {
                throw new InvalidArgumentException('Listener handler must be a callable');
            }

            $handler($this, $event, $result);
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
