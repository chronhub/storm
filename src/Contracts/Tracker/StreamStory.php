<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Tracker;

use Chronhub\Storm\Contracts\Message\MessageDecorator;

interface StreamStory extends Story
{
    public function deferred(callable $callback): void;

    public function promise(): mixed;

    public function decorate(MessageDecorator $messageDecorator): void;

    public function hasStreamNotFound(): bool;

    public function hasStreamAlreadyExits(): bool;

    public function hasConcurrency(): bool;
}
