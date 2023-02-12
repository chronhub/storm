<?php

declare(strict_types=1);

namespace Chronhub\Storm\Reporter\Exceptions;

use Chronhub\Storm\Contracts\Reporter\ReportingFailed;

class InvalidArgumentException extends \InvalidArgumentException implements ReportingFailed
{
}
