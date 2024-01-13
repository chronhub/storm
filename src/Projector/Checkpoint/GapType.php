<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Checkpoint;

enum GapType
{
    /**
     * Gap detected and filled
     */
    case IN_GAP;

    /**
     * Stream position is a gap but there is still retry left to recover
     */
    case RECOVERABLE_GAP;

    /**
     * Stream position is a gap, and there is no retry left to recover,
     * but the projection can be terminated
     */
    case UNRECOVERABLE_GAP;
}
