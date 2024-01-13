<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Projector\ContextReader;
use Chronhub\Storm\Contracts\Projector\QueryProjector;
use Chronhub\Storm\Contracts\Projector\QuerySubscriber;

final readonly class ProjectQuery implements QueryProjector
{
    use InteractWithProjection;

    public function __construct(
        protected QuerySubscriber $subscriber,
        protected ContextReader $context,
    ) {
    }

    public function run(bool $inBackground): void
    {
        $this->describeIfNeeded();

        $this->subscriber->start($this->context, $inBackground);
    }

    public function reset(): void
    {
        $this->subscriber->resets();
    }

    public function filter(QueryFilter $queryFilter): static
    {
        $this->context->withQueryFilter($queryFilter);

        return $this;
    }

    public function keepState(): static
    {
        $this->context->withKeepState();

        return $this;
    }
}
