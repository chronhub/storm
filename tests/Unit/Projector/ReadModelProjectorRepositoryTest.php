<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Generator;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Projector\Scheme\Context;
use PHPUnit\Framework\MockObject\MockObject;
use Chronhub\Storm\Contracts\Projector\Store;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Contracts\Projector\ReadModel;
use Chronhub\Storm\Tests\Unit\Projector\Util\ProvideMockContext;
use Chronhub\Storm\Projector\Repository\ReadModelProjectorRepository;

final class ReadModelProjectorRepositoryTest extends UnitTestCase
{
    use ProvideMockContext {
        setUp as contextSetup;
    }

    private Store|MockObject $store;

    private ReadModel|MockObject $readModel;

    protected function setUp(): void
    {
        $this->contextSetup();

        $this->store = $this->createMock(Store::class);
        $this->readModel = $this->createMock(ReadModel::class);
    }

    private function provideRiseExpectations(bool $exists, bool $initialized): void
    {
        $this->store->expects($this->once())->method('exists')->willReturn($exists);

        $exists
            ? $this->store->expects($this->never())->method('create')
            : $this->store->expects($this->once())->method('create')->willReturn(true);

        $this->store->expects($this->once())->method('acquireLock')->willReturn(true);
        $this->readModel->expects($this->once())->method('isInitialized')->willReturn($initialized);

        $initialized
            ? $this->readModel->expects($this->never())->method('initialize')
            : $this->readModel->expects($this->once())->method('initialize');
    }

    /**
     * @test
     *
     * @dataProvider provideBooleanForRise
     */
    public function it_rise_projection_from_streams(bool $exists, bool $initialized): void
    {
        $this->provideRiseExpectations($exists, $initialized);

        $this->position->expects($this->once())->method('watch')->with(['names' => ['some_stream_name']]);
        $this->store->expects($this->once())->method('loadState')->willReturn(true);

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
        $this->provideRiseExpectations($exists, $initialized);

        $this->position->expects($this->once())->method('watch')->with(['all' => true]);
        $this->store->expects($this->once())->method('loadState')->willReturn(true);

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
        $this->provideRiseExpectations($exists, $initialized);

        $this->position->expects($this->once())->method('watch')->with(['categories' => ['some_category']]);

        $this->store->expects($this->once())->method('loadState')->willReturn(true);

        $context = $this->newContext();
        $repository = $this->readModelRepositoryInstance($context->fromCategories('some_category'));

        $repository->rise();
    }

    /**
     * @test
     */
    public function it_store_projection(): void
    {
        $this->store->expects($this->once())->method('persist')->willReturn(true);
        $this->readModel->expects($this->once())->method('persist');

        $context = $this->newContext();

        $repository = $this->readModelRepositoryInstance($context);

        $repository->store();
    }

    /**
     * @test
     */
    public function it_revise_projection(): void
    {
        $this->store->expects($this->once())->method('reset')->willReturn(true);
        $this->readModel->expects($this->once())->method('reset');

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
        $this->store->expects($this->once())->method('delete')->with($withEmittedEvents)->willReturn(true);

        $withEmittedEvents
            ? $this->readModel->expects($this->once())->method('down')
            : $this->readModel->expects($this->never())->method('down');

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
        $this->store->expects($this->once())->method('loadState')->willReturn($loaded);

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
        $this->store->expects($this->once())->method('stop')->willReturn($closed);

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
        $this->store->expects($this->once())->method('startAgain')->willReturn($restarted);

        $repository = $this->readModelRepositoryInstance($this->newContext());

        $repository->restart();
    }

    /**
     * @test
     */
    public function it_disclose_status_projection(): void
    {
        $this->store->expects($this->once())->method('loadStatus')->willReturn(ProjectionStatus::RUNNING);

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
        $this->store->expects($this->once())->method('updateLock')->willReturn($updated);

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
        $this->store->expects($this->once())->method('releaseLock')->willReturn($unlock);

        $repository = $this->readModelRepositoryInstance($this->newContext());

        $repository->freed();
    }

    /**
     * @test
     */
    public function it_return_stream_name(): void
    {
        $this->store->expects($this->once())->method('currentStreamName')->willReturn('foo');

        $repository = $this->readModelRepositoryInstance($this->newContext());

        $this->assertEquals('foo', $repository->streamName());
    }

    private function readModelRepositoryInstance(Context $context): ReadModelProjectorRepository
    {
        return new ReadModelProjectorRepository($context, $this->store, $this->readModel);
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
