<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Chronicler;

interface ChroniclerDecorator extends Chronicler
{
    public function innerChronicler(): Chronicler;
}
