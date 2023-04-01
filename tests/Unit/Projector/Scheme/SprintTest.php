<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Scheme;

use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Projector\Scheme\Sprint;

final class SprintTest extends UnitTestCase
{
    public function testInstance(): void
    {
        $sprint = new Sprint();

        $this->assertFalse($sprint->inBackground());
        $this->assertFalse($sprint->inProgress());
    }

    public function testStopSprint(): void
    {
        $sprint = new Sprint();

        $sprint->continue();

        $this->assertTrue($sprint->inProgress());

        $sprint->stop();

        $this->assertFalse($sprint->inProgress());
    }

    public function testRunInBackground(): void
    {
        $sprint = new Sprint();

        $sprint->runInBackground(true);

        $this->assertTrue($sprint->inBackground());

        $sprint->runInBackground(false);

        $this->assertFalse($sprint->inBackground());
    }
}
