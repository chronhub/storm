<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Message;

interface EventHeader
{
    public const AGGREGATE_ID = '__aggregate_id';

    public const AGGREGATE_ID_TYPE = '__aggregate_id_type';

    public const AGGREGATE_TYPE = '__aggregate_type';

    public const AGGREGATE_VERSION = '__aggregate_version';

    public const INTERNAL_POSITION = '__internal_position';

    public const EVENT_CAUSATION_ID = '__event_causation_id';

    public const EVENT_CAUSATION_TYPE = '__event_causation_type';
}
