<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Projector\Subscription\Notification\ExpectUserState;
use Closure;
use DateInterval;
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

    public function withQueryFilter(QueryFilter $queryFilter): static
    {
        $this->context->withQueryFilter($queryFilter);

        return $this;
    }

    public function until(DateInterval|string|int $interval): static
    {
        $this->context->until($interval);

        return $this;
    }

    public function withKeepState(): static
    {
        $this->context->withKeepState();

        return $this;
    }

    public function withId(string $id): static
    {
        $this->context->withId($id);

        return $this;
    }

    public function getState(): array
    {
        return $this->subscriber->hub()->expect(ExpectUserState::class);
    }

    protected function identifyProjectionIfNeeded(): void
    {
        if ($this->context->id() !== null) {
            return;
        }

        $prefix = Str::kebab(class_basename($this));

        if (property_exists($this, 'streamName')) {
            $prefix .= '.'.$this->streamName;
        }

        $id = $prefix.'.'.Str::kebab(class_basename($this->context->queries()));

        $this->context->withId($id);
    }
}
