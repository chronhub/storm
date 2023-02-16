<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Chronicler;

use Chronhub\Storm\Contracts\Tracker\Listener;

interface EventableChronicler extends ChroniclerDecorator
{
    public const FIRST_COMMIT_EVENT = 'first_commit_stream';

    public const PERSIST_STREAM_EVENT = 'persist_stream';

    public const DELETE_STREAM_EVENT = 'delete_stream';

    public const ALL_STREAM_EVENT = 'all_stream';

    public const ALL_REVERSED_STREAM_EVENT = 'all_reversed_stream';

    public const FILTERED_STREAM_EVENT = 'filtered_stream';

    public const FILTER_STREAM_NAMES = 'filter_stream_names';

    public const FILTER_CATEGORY_NAMES = 'filter_category_names';

    public const HAS_STREAM_EVENT = 'has_stream';

    public function subscribe(string $eventName, callable $eventContext, int $priority = 0): Listener;

    public function unsubscribe(Listener ...$eventSubscribers): void;
}
