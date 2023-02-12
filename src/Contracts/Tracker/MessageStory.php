<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Tracker;

use Generator;
use Chronhub\Storm\Message\Message;
use React\Promise\PromiseInterface;

interface MessageStory extends Story
{
    /**
     * Set transient message
     *
     * @param  object|array  $transientMessage
     * @return void
     */
    public function withTransientMessage(object|array $transientMessage): void;

    /**
     * Return transient message
     *
     * @return object|array|null
     */
    public function transientMessage(): null|object|array;

    /**
     * Pull transient message
     *
     * Should be done once to be replaced by
     * a valid message instance
     *
     * @return object|array
     */
    public function pullTransientMessage(): object|array;

    /**
     * Set valid message instance
     *
     * @param  Message  $message
     * @return void
     */
    public function withMessage(Message $message): void;

    /**
     * Return current message
     *
     * @return Message
     */
    public function message(): Message;

    /**
     * Add message handlers
     *
     * @param  iterable  $consumers
     * @return void
     */
    public function withConsumers(iterable $consumers): void;

    /**
     * Yield consumers
     *
     * @return Generator
     */
    public function consumers(): Generator;

    /**
     * Mark message handled
     *
     * @param  bool  $isMessageHandled
     * @return void
     */
    public function markHandled(bool $isMessageHandled): void;

    /**
     * Check if message has been handled
     *
     * @return bool
     */
    public function isHandled(): bool;

    /**
     * Set promise
     *
     * @param  PromiseInterface  $promise
     * @return void
     */
    public function withPromise(PromiseInterface $promise): void;

    /**
     * Return promise if exists
     *
     * @return PromiseInterface|null
     */
    public function promise(): ?PromiseInterface;
}
