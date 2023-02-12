<?php

declare(strict_types=1);

namespace Chronhub\Storm\Chronicler\Exceptions;

use UnexpectedValueException;
use Chronhub\Storm\Contracts\Chronicler\ChroniclerFailure;

class UnexpectedCallback extends UnexpectedValueException implements ChroniclerFailure
{
}
