<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Exceptions;

use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;
use Chronhub\Storm\Projector\Exceptions\RuntimeException;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ProjectionNotFound::class)]
final class ProjectionNotFoundTest extends UnitTestCase
{
    public function testException(): void
    {
        $exception = ProjectionNotFound::withName('foo');

        $this->assertInstanceOf(RuntimeException::class, $exception);
        $this->assertEquals('Projection foo not found', $exception->getMessage());
    }
}
