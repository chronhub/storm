<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Reporter;

use Chronhub\Storm\Contracts\Tracker\MessageSubscriber;
use Chronhub\Storm\Contracts\Tracker\MessageTracker;
use React\Promise\PromiseInterface;

interface Reporter
{
    public const DISPATCH_EVENT = 'dispatch_event';

    public const FINALIZE_EVENT = 'finalize_event';

    /**
     * @return void|PromiseInterface
     */
    public function relay(object|array $message);

    public function subscribe(MessageSubscriber ...$messageSubscribers): void;

    public function tracker(): MessageTracker;
}
