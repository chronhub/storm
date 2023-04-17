<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Message;

interface EventHeader
{
    /**
     * @var string
     */
    public const AGGREGATE_ID = '__aggregate_id';

    /**
     * @var string
     */
    public const AGGREGATE_ID_TYPE = '__aggregate_id_type';

    /**
     * @var string
     */
    public const AGGREGATE_TYPE = '__aggregate_type';

    /**
     * @var string
     */
    public const AGGREGATE_VERSION = '__aggregate_version';

    /**
     * @var string
     */
    public const INTERNAL_POSITION = '__internal_position';

    /**
     * @var string
     */
    public const EVENT_CAUSATION_ID = '__event_causation_id';

    /**
     * @var string
     */
    public const EVENT_CAUSATION_TYPE = '__event_causation_type';
}
