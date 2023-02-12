<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Chronicler;

interface ChroniclerDecorator extends Chronicler
{
    /**
     * Get the underlying chronicler instance
     *
     * @return Chronicler
     */
    public function innerChronicler(): Chronicler;
}
