<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Tracker;

use Chronhub\Storm\Contracts\Message\MessageDecorator;

interface StreamStory extends Story
{
    /**
     * @param  callable  $callback
     * @return void
     */
    public function deferred(callable $callback): void;

    /**
     * @return mixed
     */
    public function promise(): mixed;

    /**
     * Decorate stream events
     *
     * @param  MessageDecorator  $messageDecorator
     * @return void
     */
    public function decorate(MessageDecorator $messageDecorator): void;

    /**
     * @return bool
     */
    public function hasStreamNotFound(): bool;

    /**
     * @return bool
     */
    public function hasStreamAlreadyExits(): bool;

    /**
     * @return bool
     */
    public function hasConcurrency(): bool;
}
