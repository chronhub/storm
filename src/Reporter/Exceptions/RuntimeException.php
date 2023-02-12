<?php

declare(strict_types=1);

namespace Chronhub\Storm\Reporter\Exceptions;

use Chronhub\Storm\Contracts\Reporter\ReportingFailed;

class RuntimeException extends \RuntimeException implements ReportingFailed
{
}
