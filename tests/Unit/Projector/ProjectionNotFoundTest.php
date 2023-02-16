<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;

final class ProjectionNotFoundTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_assert_message_exception(): void
    {
        $exception = ProjectionNotFound::withName('foo');

        $this->assertEquals('Projection name foo not found', $exception->getMessage());
    }
}
