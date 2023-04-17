<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Chronicler;

use Chronhub\Storm\Contracts\Tracker\Listener;

interface EventableChronicler extends ChroniclerDecorator
{
    /**
     * @var string
     */
    public const FIRST_COMMIT_EVENT = 'first_commit_stream';

    /**
     * @var string
     */
    public const PERSIST_STREAM_EVENT = 'persist_stream';

    /**
     * @var string
     */
    public const DELETE_STREAM_EVENT = 'delete_stream';

    /**
     * @var string
     */
    public const ALL_STREAM_EVENT = 'all_stream';

    /**
     * @var string
     */
    public const ALL_REVERSED_STREAM_EVENT = 'all_reversed_stream';

    /**
     * @var string
     */
    public const FILTERED_STREAM_EVENT = 'filtered_stream';

    /**
     * @var string
     */
    public const FILTER_STREAM_NAMES = 'filter_stream_names';

    /**
     * @var string
     */
    public const FILTER_CATEGORY_NAMES = 'filter_category_names';

    /**
     * @var string
     */
    public const HAS_STREAM_EVENT = 'has_stream';

    public function subscribe(string $eventName, callable $eventContext, int $priority = 0): Listener;

    public function unsubscribe(Listener ...$eventSubscribers): void;
}
