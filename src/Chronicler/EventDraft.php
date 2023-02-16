<?php

declare(strict_types=1);

namespace Chronhub\Storm\Chronicler;

use Chronhub\Storm\Stream\Stream;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Tracker\InteractWithStory;
use Chronhub\Storm\Contracts\Tracker\StreamStory;
use Chronhub\Storm\Contracts\Message\MessageDecorator;
use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Chronicler\Exceptions\UnexpectedCallback;
use Chronhub\Storm\Chronicler\Exceptions\StreamAlreadyExists;
use Chronhub\Storm\Chronicler\Exceptions\ConcurrencyException;

class EventDraft implements StreamStory
{
    use InteractWithStory;

    /**
     * @var callable
     */
    private $callback;

    public function deferred(callable $callback): void
    {
        $this->callback = $callback;
    }

    public function promise(): mixed
    {
        if (null === $this->callback) {
            throw new UnexpectedCallback('No event callback has been set');
        }

        return ($this->callback)();
    }

    public function decorate(MessageDecorator $messageDecorator): void
    {
        $stream = $this->promise();

        if (! $stream instanceof Stream) {
            throw new UnexpectedCallback('No stream has been set as event callback');
        }

        $events = [];

        foreach ($stream->events() as $streamEvent) {
            $events[] = $messageDecorator->decorate(new Message($streamEvent))->event();
        }

        $this->deferred(fn (): Stream => new Stream($stream->name(), $events));
    }

    public function hasStreamNotFound(): bool
    {
        return $this->exception instanceof StreamNotFound;
    }

    public function hasStreamAlreadyExits(): bool
    {
        return $this->exception instanceof StreamAlreadyExists;
    }

    public function hasConcurrency(): bool
    {
        return $this->exception instanceof ConcurrencyException;
    }
}
