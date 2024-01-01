<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Stream;

enum GapType
{
    /**
     * gap detected and filled
     */
    case IN_GAP;

    /**
     * stream position is a gap but there is still retry left to recover
     */
    case RECOVERABLE_GAP;

    /**
     * stream position is a gap, and there is no retry left to recover
     * but the projection can be terminated
     */
    case UNRECOVERABLE_GAP;
}
