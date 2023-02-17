<?php

declare(strict_types=1);

namespace Chronhub\Storm\Reporter;

enum OnFinalizePriority: int
{
    case FINALIZE_TRANSACTION = 1000;

    case GUARD_QUERY = 500;
}
