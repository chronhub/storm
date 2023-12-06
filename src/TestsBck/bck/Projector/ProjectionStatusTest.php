<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ProjectionStatus::class)]
final class ProjectionStatusTest extends UnitTestCase
{
    public function testFixEnumStrings(): void
    {
        $this->assertSame(
            ['running', 'stopping', 'deleting', 'deleting_with_emitted_events', 'resetting', 'idle'],
            ProjectionStatus::strings()
        );
    }
}
