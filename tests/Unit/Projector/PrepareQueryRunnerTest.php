<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Closure;
use Chronhub\Storm\Tests\ProphecyTestCase;
use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Projector\Pipes\PrepareQueryRunner;
use Chronhub\Storm\Tests\Unit\Projector\Util\ProvideContextWithProphecy;

final class PrepareQueryRunnerTest extends ProphecyTestCase
{
    use ProvideContextWithProphecy;

    /**
     * @test
     */
    public function it_initiate_by_loading_streams(): void
    {
        $this->position->watch(['names' => ['add', 'subtract']])->shouldBeCalledOnce();

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
