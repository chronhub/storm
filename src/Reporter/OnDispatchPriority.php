<?php

declare(strict_types=1);

namespace Chronhub\Storm\Reporter;

enum OnDispatchPriority: int
{
    case MESSAGE_FACTORY = 100000;

    case MESSAGE_DECORATOR = 90000;

    case MESSAGE_VALIDATION = 50000;

    case GUARD_COMMAND = 25000;

    case ROUTE = 20000;

    case GUARD_QUERY = 5000;

    case START_TRANSACTION = 2000;

    case INVOKE_HANDLER = 0;
}
