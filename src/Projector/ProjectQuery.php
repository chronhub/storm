<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Projector\ContextReaderInterface;
use Chronhub\Storm\Contracts\Projector\QueryProjector;
use Chronhub\Storm\Contracts\Projector\QuerySubscriber;

final readonly class ProjectQuery implements QueryProjector
{
    use InteractWithProjection;

    public function __construct(
        protected QuerySubscriber $subscriber,
        protected ContextReaderInterface $context
    ) {
    }

    public function run(bool $inBackground): void
    {
        $this->subscriber->start($this->context, $inBackground);
    }

    public function reset(): void
    {
        $this->subscriber->resets();
    }
}
