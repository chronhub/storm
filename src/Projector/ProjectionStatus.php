<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

enum ProjectionStatus: string
{
    case RUNNING = 'running';

    case STOPPING = 'stopping';

    case DELETING = 'deleting';

    case DELETING_WITH_EMITTED_EVENTS = 'deleting_with_emitted_events';

    case RESETTING = 'resetting';

    case IDLE = 'idle';
}
