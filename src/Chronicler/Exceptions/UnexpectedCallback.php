<?php

declare(strict_types=1);

namespace Chronhub\Storm\Chronicler\Exceptions;

use Chronhub\Storm\Contracts\Chronicler\ChroniclerFailure;
use UnexpectedValueException;

class UnexpectedCallback extends UnexpectedValueException implements ChroniclerFailure
{
}
