<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Chronicler;

interface EventPublisher
{
    public function record(iterable $streamEvents): void;

    public function pull(): iterable;

    public function publish(iterable $streamEvents): void;

    public function flush(): void;
}
