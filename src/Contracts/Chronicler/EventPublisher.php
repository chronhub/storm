<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Chronicler;

interface EventPublisher
{
    /**
     * @param  iterable  $streamEvents
     * @return void
     */
    public function record(iterable $streamEvents): void;

    /**
     * @return iterable
     */
    public function pull(): iterable;

    /**
     * @param  iterable  $streamEvents
     * @return void
     */
    public function publish(iterable $streamEvents): void;

    /**
     * @return void
     */
    public function flush(): void;
}
