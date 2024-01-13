<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Support\Notification\UserState\CurrentUserState;
use Closure;
use Illuminate\Support\Str;

use function property_exists;

trait InteractWithProjection
{
    public function initialize(Closure $userState): static
    {
        $this->context->initialize($userState);

        return $this;
    }

    public function subscribeToStream(string ...$streams): static
    {
        $this->context->subscribeToStream(...$streams);

        return $this;
    }

    public function subscribeToCategory(string ...$categories): static
    {
        $this->context->subscribeToCategory(...$categories);

        return $this;
    }

    public function subscribeToAll(): static
    {
        $this->context->subscribeToAll();

        return $this;
    }

    public function when(Closure $reactors): static
    {
        $this->context->when($reactors);

        return $this;
    }

    public function haltOn(Closure $haltOn): static
    {
        $this->context->haltOn($haltOn);

        return $this;
    }

    public function describe(string $id): static
    {
        $this->context->withId($id);

        return $this;
    }

    public function getState(): array
    {
        return $this->subscriber->interact(
            fn (NotificationHub $hub): array => $hub->expect(CurrentUserState::class)
        );
    }

    protected function describeIfNeeded(): void
    {
        if ($this->context->id() === null) {
            $prefix = Str::kebab(class_basename($this));

            if (property_exists($this, 'streamName')) {
                $prefix .= '.'.$this->streamName;
            }

            $id = $prefix.'.'.Str::kebab(class_basename($this->context->queries()));

            $this->context->withId($id);
        }
    }
}
