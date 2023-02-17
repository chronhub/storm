<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Reporter;

use React\Promise\PromiseInterface;
use Chronhub\Storm\Contracts\Tracker\MessageTracker;
use Chronhub\Storm\Contracts\Tracker\MessageSubscriber;

interface Reporter
{
    public const DISPATCH_EVENT = 'dispatch_event';

    public const FINALIZE_EVENT = 'finalize_event';

    /**
     * Report message
     *
     * @return void|PromiseInterface
     */
    public function relay(object|array $message);

    /**
     * Subscribe to the underlying reporter tracker
     */
    public function subscribe(MessageSubscriber ...$messageSubscribers): void;

    /**
     * Get the message tracker
     */
    public function tracker(): MessageTracker;
}
