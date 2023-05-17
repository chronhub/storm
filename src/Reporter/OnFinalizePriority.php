<?php

declare(strict_types=1);

namespace Chronhub\Storm\Reporter;

enum OnFinalizePriority: int
{
    case FINALIZE_TRANSACTION = 10000;

    case GUARD_QUERY = 5000;
}
