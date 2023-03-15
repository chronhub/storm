<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Pipes;

use Closure;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Projector\Scheme\Context;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Projector\Pipes\PrepareQueryRunner;
use Chronhub\Storm\Tests\Unit\Projector\Mock\ProvideMockContext;

#[CoversClass(PrepareQueryRunner::class)]
final class PrepareQueryRunnerTest extends UnitTestCase
{
    use ProvideMockContext;

    #[Test]
    public function it_initiate_by_loading_streams(): void
    {
        $this->position->expects($this->once())->method('watch')->with(['names' => ['add', 'subtract']]);

        $context = $this->newContext();

        $context->fromStreams('add', 'subtract');

        $context->runner->runInBackground(false);

        $pipe = new PrepareQueryRunner();

        $this->assertIsInitialized($pipe, false);

        $run = $pipe($context, fn (Context $context): bool => true);

        $this->assertTrue($run);

        $this->assertIsInitialized($pipe, true);
    }

    private function assertIsInitialized(PrepareQueryRunner $instance, bool $expect): void
    {
        $closure = Closure::bind(
            static fn ($instance) => $instance->isInitialized, null, PrepareQueryRunner::class
        );

        $this->assertEquals($expect, $closure($instance));
    }
}
