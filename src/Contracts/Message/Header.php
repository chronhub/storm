<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Message;

interface Header
{
    public const EVENT_ID = '__event_id';

    public const EVENT_TYPE = '__event_type';

    public const EVENT_TIME = '__event_time';

    public const REPORTER_ID = '__reporter_id';

    public const EVENT_STRATEGY = '__event_strategy';

    public const EVENT_DISPATCHED = '__event_dispatched';
}
