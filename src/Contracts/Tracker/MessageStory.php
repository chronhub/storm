<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Tracker;

use Chronhub\Storm\Message\Message;
use Generator;
use React\Promise\PromiseInterface;

interface MessageStory extends Story
{
    /**
     * Set transient message
     */
    public function withTransientMessage(object|array $transientMessage): void;

    /**
     * Return transient message
     */
    public function transientMessage(): null|object|array;

    /**
     * Pull transient message
     *
     * Should be done once to be replaced by
     * a valid message instance
     */
    public function pullTransientMessage(): object|array;

    /**
     * Set valid message instance
     */
    public function withMessage(Message $message): void;

    /**
     * Return current message
     */
    public function message(): Message;

    /**
     * Add message handlers
     */
    public function withConsumers(iterable $consumers): void;

    /**
     * Yield consumers
     */
    public function consumers(): Generator;

    /**
     * Mark message handled
     */
    public function markHandled(bool $isMessageHandled): void;

    /**
     * Check if message has been handled
     */
    public function isHandled(): bool;

    /**
     * Set promise
     */
    public function withPromise(PromiseInterface $promise): void;

    /**
     * Return promise if exists
     */
    public function promise(): ?PromiseInterface;
}
