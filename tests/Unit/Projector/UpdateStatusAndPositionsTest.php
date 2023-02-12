<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Generator;
use Chronhub\Storm\Tests\ProphecyTestCase;
use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Pipes\UpdateStatusAndPositions;
use Chronhub\Storm\Tests\Unit\Projector\Util\ProvideContextWithProphecy;

final class UpdateStatusAndPositionsTest extends ProphecyTestCase
{
    use ProvideContextWithProphecy;

    /**
     * @test
     *
     * @dataProvider provideBoolean
     */
    public function it_reload_remote_status(bool $runInBackground): void
    {
        $status = ProjectionStatus::IDLE;

        $this->repository->disclose()->willReturn($status)->shouldBeCalledOnce();

        $context = $this->newContext();
        $context->fromStreams('add');
        $context->runner->runInBackground($runInBackground);

        $this->position->watch(['names' => ['add']])->shouldBeCalledOnce();

        $pipe = new UpdateStatusAndPositions($this->repository->reveal());

        $run = $pipe($context, fn (Context $context): bool => true);

        $this->assertTrue($run);
    }

    /**
     * @test
     *
     * @dataProvider provideBoolean
     */
    public function it_stop_on_stop_status_loaded(bool $runInBackground): void
    {
        $status = ProjectionStatus::STOPPING;

        $this->repository->disclose()->willReturn($status)->shouldBeCalled();

        $this->repository->boundState()->shouldNotBeCalled();

        $this->repository->close()->shouldBeCalledOnce();

        $context = $this->newContext();

        $context->fromStreams('add');

        $context->runner->runInBackground($runInBackground);

        $this->position->watch(['names' => ['add']])->shouldBeCalled();

        $pipe = new UpdateStatusAndPositions($this->repository->reveal());

        $run = $pipe($context, fn (Context $context): bool => true);

        $this->assertTrue($run);
    }

    /**
     * @test
     *
     * @dataProvider provideBoolean
     */
    public function it_reset_on_resetting_status_loaded(bool $runInBackground): void
    {
        $status = ProjectionStatus::RESETTING;

        $this->repository->disclose()->willReturn($status)->shouldBeCalled();

        $this->repository->revise()->shouldBeCalled();

        $runInBackground
            ? $this->repository->restart()->shouldBeCalledOnce()
            : $this->repository->restart()->shouldNotBeCalled();

        $context = $this->newContext();

        $context->fromStreams('add');
        $context->runner->runInBackground($runInBackground);

        $this->position->watch(['names' => ['add']])->shouldBeCalled();

        $pipe = new UpdateStatusAndPositions($this->repository->reveal());

        $run = $pipe($context, fn (Context $context): bool => true);

        $this->assertTrue($run);
    }

    /**
     * @test
     *
     * @dataProvider provideBoolean
     */
    public function it_delete_on_deleting_status_loaded(bool $runInBackground): void
    {
        $status = ProjectionStatus::DELETING;

        $this->repository->disclose()->willReturn($status)->shouldBeCalledOnce();

        $this->repository->discard(false)->shouldBeCalledOnce();

        $context = $this->newContext();

        $context->fromStreams('add');

        $this->position->watch(['names' => ['add']])->shouldBeCalled();

        $pipe = new UpdateStatusAndPositions($this->repository->reveal());

        $run = $pipe($context, fn (Context $context): bool => true);

        $this->assertTrue($run);
    }

    /**
     * @test
     *
     * @dataProvider provideBoolean
     */
    public function it_delete_with_events_on_deleting_with_emitted_events_status_loaded(bool $runInBackground): void
    {
        $status = ProjectionStatus::DELETING_WITH_EMITTED_EVENTS;

        $this->repository->disclose()->willReturn($status)->shouldBeCalledOnce();

        $this->repository->discard(true)->shouldBeCalledOnce();

        $context = $this->newContext();

        $context->fromStreams('add');

        $context->runner->runInBackground($runInBackground);

        $this->position->watch(['names' => ['add']])->shouldBeCalled();

        $pipe = new UpdateStatusAndPositions($this->repository->reveal());

        $run = $pipe($context, fn (Context $context): bool => true);

        $this->assertTrue($run);
    }

    public function provideBoolean(): Generator
    {
        yield [true];

        yield [false];
    }
}
