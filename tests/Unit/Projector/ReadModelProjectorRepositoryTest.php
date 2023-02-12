<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Generator;
use Prophecy\Prophecy\ObjectProphecy;
use Chronhub\Storm\Tests\ProphecyTestCase;
use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Contracts\Projector\Store;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Projector\Repository\ReadModelProjectorRepository;
use Chronhub\Storm\Tests\Unit\Projector\Util\ProvideContextWithProphecy;

final class ReadModelProjectorRepositoryTest extends ProphecyTestCase
{
    use ProvideContextWithProphecy {
        setUp as contextSetup;
    }

    private Store|ObjectProphecy $store;

    private ReadModel|ObjectProphecy $readModel;

    protected function setUp(): void
    {
        $this->contextSetup();

        $this->store = $this->prophesize(Store::class);
        $this->readModel = $this->prophesize(ReadModel::class);
    }

    /**
     * @test
     *
     * @dataProvider provideBooleanForRise
     */
    public function it_rise_projection_from_streams(bool $exists, bool $initialized): void
    {
        $this->store->exists()->willReturn($exists)->shouldBeCalledOnce();

        $exists
            ? $this->store->create()->shouldNotBeCalled()
            : $this->store->create()->willReturn(true)->shouldBeCalledOnce();

        $this->store->acquireLock()->willReturn(true)->shouldBeCalledOnce();

        $this->readModel->isInitialized()->willReturn($initialized)->shouldBeCalledOnce();

        $initialized
            ? $this->readModel->initialize()->shouldNotBeCalled()
            : $this->readModel->initialize()->shouldBeCalledOnce();

        $this->position->watch(['names' => ['some_stream_name']])->shouldBeCalledOnce();

        $this->store->loadState()->willReturn(true)->shouldBeCalledOnce();

        $context = $this->newContext();
        $repository = $this->readModelRepositoryInstance($context->fromStreams('some_stream_name'));

        $repository->rise();
    }

    /**
     * @test
     *
     * @dataProvider provideBooleanForRise
     */
    public function it_rise_projection_from_all_streams(bool $exists, bool $initialized): void
    {
        $this->store->exists()->willReturn($exists)->shouldBeCalledOnce();

        $exists
            ? $this->store->create()->shouldNotBeCalled()
            : $this->store->create()->willReturn(true)->shouldBeCalledOnce();

        $this->store->acquireLock()->willReturn(true)->shouldBeCalledOnce();

        $this->readModel->isInitialized()->willReturn($initialized)->shouldBeCalledOnce();

        $initialized
            ? $this->readModel->initialize()->shouldNotBeCalled()
            : $this->readModel->initialize()->shouldBeCalledOnce();

        $this->position->watch(['all' => true])->shouldBeCalledOnce();

        $this->store->loadState()->willReturn(true)->shouldBeCalledOnce();

        $context = $this->newContext();
        $repository = $this->readModelRepositoryInstance($context->fromAll());

        $repository->rise();
    }

    /**
     * @test
     *
     * @dataProvider provideBooleanForRise
     */
    public function it_rise_projection_from_categories(bool $exists, bool $initialized): void
    {
        $this->store->exists()->willReturn($exists)->shouldBeCalledOnce();

        $exists
            ? $this->store->create()->shouldNotBeCalled()
            : $this->store->create()->willReturn(true)->shouldBeCalledOnce();

        $this->store->acquireLock()->willReturn(true)->shouldBeCalledOnce();

        $this->readModel->isInitialized()->willReturn($initialized)->shouldBeCalledOnce();

        $initialized
            ? $this->readModel->initialize()->shouldNotBeCalled()
            : $this->readModel->initialize()->shouldBeCalledOnce();

        $this->position->watch(['categories' => ['some_category']])->shouldBeCalledOnce();

        $this->store->loadState()->willReturn(true)->shouldBeCalledOnce();

        $context = $this->newContext();
        $repository = $this->readModelRepositoryInstance($context->fromCategories('some_category'));

        $repository->rise();
    }

    /**
     * @test
     */
    public function it_store_projection(): void
    {
        $this->store->persist()->willReturn(true)->shouldBeCalledOnce();
        $this->readModel->persist()->shouldBeCalledOnce();

        $context = $this->newContext();

        $repository = $this->readModelRepositoryInstance($context);

        $repository->store();
    }

    /**
     * @test
     */
    public function it_revise_projection(): void
    {
        $this->store->reset()->willReturn(true)->shouldBeCalledOnce();
        $this->readModel->reset()->shouldBeCalledOnce();

        $repository = $this->readModelRepositoryInstance($this->newContext());

        $repository->revise();
    }

    /**
     * @test
     *
     * @dataProvider provideBoolean
     */
    public function it_discard_projection(bool $withEmittedEvents): void
    {
        $this->store->delete($withEmittedEvents)->willReturn(true)->shouldBeCalledOnce();

        $withEmittedEvents
            ? $this->readModel->down()->shouldBeCalledOnce()
            : $this->readModel->down()->shouldNotBeCalled();

        $repository = $this->readModelRepositoryInstance($this->newContext());

        $repository->discard($withEmittedEvents);
    }

    /**
     * @test
     *
     * @dataProvider provideBoolean
     */
    public function it_bound_state($loaded): void
    {
        $this->store->loadState()->willReturn($loaded)->shouldBeCalledOnce();

        $repository = $this->readModelRepositoryInstance($this->newContext());

        $repository->boundState();
    }

    /**
     * @test
     *
     * @dataProvider provideBoolean
     */
    public function it_close_projection($closed): void
    {
        $this->store->stop()->willReturn($closed)->shouldBeCalledOnce();

        $repository = $this->readModelRepositoryInstance($this->newContext());

        $repository->close();
    }

    /**
     * @test
     *
     * @dataProvider provideBoolean
     */
    public function it_restart_projection($restarted): void
    {
        $this->store->startAgain()->willReturn($restarted)->shouldBeCalledOnce();

        $repository = $this->readModelRepositoryInstance($this->newContext());

        $repository->restart();
    }

    /**
     * @test
     */
    public function it_disclose_status_projection(): void
    {
        $this->store->loadStatus()->willReturn(ProjectionStatus::RUNNING)->shouldBeCalledOnce();

        $repository = $this->readModelRepositoryInstance($this->newContext());

        $this->assertEquals(ProjectionStatus::RUNNING, $repository->disclose());
    }

    /**
     * @test
     *
     * @dataProvider provideBoolean
     */
    public function it_renew_projection_lock($updated): void
    {
        $this->store->updateLock()->willReturn($updated)->shouldBeCalledOnce();

        $repository = $this->readModelRepositoryInstance($this->newContext());

        $repository->renew();
    }

    /**
     * @test
     *
     * @dataProvider provideBoolean
     */
    public function it_freed_projection_lock(bool $unlock): void
    {
        $this->store->releaseLock()->willReturn($unlock)->shouldBeCalledOnce();

        $repository = $this->readModelRepositoryInstance($this->newContext());

        $repository->freed();
    }

    /**
     * @test
     */
    public function it_return_stream_name(): void
    {
        $this->store->currentStreamName()->willReturn('foo')->shouldBeCalledOnce();

        $repository = $this->readModelRepositoryInstance($this->newContext());

        $this->assertEquals('foo', $repository->streamName());
    }

    private function readModelRepositoryInstance(Context $context): ReadModelProjectorRepository
    {
        return new ReadModelProjectorRepository(
            $context, $this->store->reveal(), $this->readModel->reveal()
        );
    }

    public function provideBooleanForRise(): Generator
    {
        yield [false, false];
        yield [true, true];
        yield [true, false];
        yield [false, true];
    }

    public function provideBoolean(): Generator
    {
        yield [false];
        yield [true];
    }
}
