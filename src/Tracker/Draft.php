<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tracker;

use Generator;
use Chronhub\Storm\Message\Message;
use React\Promise\PromiseInterface;
use Chronhub\Storm\Contracts\Tracker\MessageStory;

class Draft implements MessageStory
{
    use InteractWithStory;

    /**
     * The message instance
     */
    private ?Message $message;

    /**
     * Transient message
     *
     * A message factory should be responsible to produce a valid message instance
     */
    private object|array|null $transientMessage = null;

    /**
     * Message handler(s)
     *
     * @var iterable<object>
     */
    private iterable $consumers = [];

    /**
     * Is message handled
     */
    private bool $isHandled = false;

    /**
     * Promise interface available for domain query
     */
    private ?PromiseInterface $promise = null;

    public function withTransientMessage(object|array $transientMessage): void
    {
        $this->transientMessage = $transientMessage;
    }

    public function withMessage(Message $message): void
    {
        $this->message = $message;
    }

    public function withConsumers(iterable $consumers): void
    {
        $this->consumers = $consumers;
    }

    public function consumers(): Generator
    {
        yield from $this->consumers;
    }

    public function withPromise(PromiseInterface $promise): void
    {
        $this->promise = $promise;
    }

    public function markHandled(bool $isMessageHandled): void
    {
        $this->isHandled = $isMessageHandled;
    }

    public function isHandled(): bool
    {
        return $this->isHandled;
    }

    public function transientMessage(): null|object|array
    {
        return $this->transientMessage;
    }

    public function pullTransientMessage(): object|array
    {
        $transientMessage = $this->transientMessage;

        $this->transientMessage = null;

        return $transientMessage;
    }

    public function message(): Message
    {
        return $this->message;
    }

    public function promise(): ?PromiseInterface
    {
        return $this->promise;
    }
}
