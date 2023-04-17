<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Message;

interface Header
{
    /**
     * @var string
     */
    public const EVENT_ID = '__event_id';

    /**
     * @var string
     */
    public const EVENT_TYPE = '__event_type';

    /**
     * @var string
     */
    public const EVENT_TIME = '__event_time';

    /**
     * @var string
     */
    public const REPORTER_ID = '__reporter_id';

    /**
     * @var string
     */
    public const EVENT_STRATEGY = '__event_strategy';

    /**
     * @var string
     */
    public const EVENT_DISPATCHED = '__event_dispatched';
}
