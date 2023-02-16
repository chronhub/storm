<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Stubs;

use Chronhub\Storm\Projector\Scheme\Context;

final class ContextStub extends Context
{
    public static function newInstance($option, $position, $counter, $gap): self
    {
        return new self($option, $position, $counter, $gap);
    }

    public function validateStub(): void
    {
        $this->validate();
    }
}
