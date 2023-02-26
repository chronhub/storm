<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Closure;
use Generator;
use Chronhub\Storm\Tests\ProphecyTestCase;
use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Pipes\PreparePersistentRunner;
use Chronhub\Storm\Tests\Unit\Projector\Util\ProvideContextWithProphecy;

final class PreparePersistentRunnerTest extends ProphecyTestCase
{
    use ProvideContextWithProphecy;

    /**
     * @test
     *
     * @dataProvider provideBoolean
     */
    public function it_initiate_projection_when_loading_status_from_remote(bool $runInBackground): void
    {
        $status = ProjectionStatus::RUNNING;

        $this->repository->disclose()->willReturn($status)->shouldBeCalledOnce();

        $this->repository->rise()->shouldBeCalledOnce();

        $context = $this->newContext();

        $context->runner->runInBackground($runInBackground);

        $pipe = new PreparePersistentRunner($this->repository->reveal());

        $this->assertIsInitialized($pipe, false);

        $run = $pipe($context, fn (Context $context): bool => true);

        $this->assertTrue($run);
        $this->assertIsInitialized($pipe, true);
    }

    /**
     * @test
     *
     * @dataProvider provideBoolean
     */
    public function it_stop_projection_when_remote_status_is_stopping(bool $runInBackground): void
    {
        $status = ProjectionStatus::STOPPING;

        $this->repository->disclose()->willReturn($status)->shouldBeCalledOnce();

        $this->repository->boundState()->shouldBeCalledOnce();

        $this->repository->close()->shouldBeCalledOnce();

        $this->repository->rise()->shouldNotBeCalled();

        $context = $this->newContext();
        $context->runner->runInBackground($runInBackground);

        $pipe = new PreparePersistentRunner($this->repository->reveal());

        $this->assertIsInitialized($pipe, false);

        $run = $pipe($context, fn (Context $context): bool => true);

        $this->assertTrue($run);

        $this->assertIsInitialized($pipe, true);
    }

    /**
     * @test
     *
     * @dataProvider provideBoolean
     */
    public function it_stop_projection_when_remote_status_is_resetting(bool $runInBackground): void
    {
        $status = ProjectionStatus::RESETTING;

        $this->repository->disclose()->willReturn($status)->shouldBeCalledOnce();

        $this->repository->revise()->shouldBeCalledOnce();

        $this->repository->restart()->shouldNotBeCalled();

        $this->repository->rise()->shouldBeCalled();

        $context = $this->newContext();
        $context->runner->runInBackground($runInBackground);

        $pipe = new PreparePersistentRunner($this->repository->reveal());

        $this->assertIsInitialized($pipe, false);

        $run = $pipe($context, fn (Context $context): bool => true);

        $this->assertTrue($run);

        $this->assertIsInitialized($pipe, true);
    }

    /**
     * @test
     *
     * @dataProvider provideBoolean
     */
    public function it_initiate_projection_when_remote_status_is_deleting(bool $runInBackground): void
    {
        $status = ProjectionStatus::DELETING;

        $this->repository->disclose()->willReturn($status)->shouldBeCalledOnce();

        $this->repository->discard(false)->shouldBeCalledOnce();

        $this->repository->restart()->shouldNotBeCalled();

        $this->repository->rise()->shouldNotBeCalled();

        $context = $this->newContext();

        $context->runner->runInBackground($runInBackground);

        $pipe = new PreparePersistentRunner($this->repository->reveal());

        $this->assertIsInitialized($pipe, false);

        $run = $pipe($context, fn (Context $context): bool => true);

        $this->assertTrue($run);

        $this->assertIsInitialized($pipe, true);
    }

    /**
     * @test
     *
     * @dataProvider provideBoolean
     */
    public function it_initiate_projection_when_remote_status_is_deleting_with_emitted_events(bool $runInBackground): void
    {
        $status = ProjectionStatus::DELETING_WITH_EMITTED_EVENTS;

        $this->repository->disclose()->willReturn($status)->shouldBeCalledOnce();

        $this->repository->discard(true)->shouldBeCalledOnce();

        $this->repository->restart()->shouldNotBeCalled();

        $this->repository->rise()->shouldNotBeCalled();

        $context = $this->newContext();

        $context->runner->runInBackground($runInBackground);

        $pipe = new PreparePersistentRunner($this->repository->reveal());

        $this->assertIsInitialized($pipe, false);

        $run = $pipe($context, fn (Context $context): bool => true);

        $this->assertTrue($run);

        $this->assertIsInitialized($pipe, true);
    }

    private function assertIsInitialized(PreparePersistentRunner $instance, bool $expect): void
    {
        $closure = Closure::bind(
            fn ($instance) => $instance->isInitialized, null, PreparePersistentRunner::class
        );

        $this->assertEquals($expect, $closure($instance));
    }

    public function provideBoolean(): Generator
    {
        yield [true];
        yield [false];
    }
}
