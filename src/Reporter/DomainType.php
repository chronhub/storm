<?php

declare(strict_types=1);

namespace Chronhub\Storm\Reporter;

enum DomainType: string
{
    case COMMAND = 'command';
    case EVENT = 'event';
    case QUERY = 'query';
}
