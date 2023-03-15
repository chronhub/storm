<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Stubs;

use Chronhub\Storm\Projector\Scheme\Context;
use PHPUnit\Framework\MockObject\MockObject;
use Chronhub\Storm\Projector\InteractWithContext;

final class InteractWithContextStub
{
    use InteractWithContext;

    public function __construct(protected Context|MockObject $context)
    {
    }
}
