<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;

#[CoversClass(ProjectionNotFound::class)]
final class ProjectionNotFoundTest extends UnitTestCase
{
    #[Test]
    public function it_assert_message_exception(): void
    {
        $exception = ProjectionNotFound::withName('foo');

        $this->assertEquals('Projection name foo not found', $exception->getMessage());
    }
}
