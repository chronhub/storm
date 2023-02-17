<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Exceptions;

use Chronhub\Storm\Contracts\Projector\ProjectorFailed;

class InvalidArgumentException extends \InvalidArgumentException implements ProjectorFailed
{
}
