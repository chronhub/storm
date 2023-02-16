<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Generator;
use Chronhub\Storm\Stream\StreamName;
use Prophecy\Prophecy\ObjectProphecy;
use Chronhub\Storm\Tests\ProphecyTestCase;
use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Contracts\Projector\Store;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Projector\Repository\PersistentProjectorRepository;
use Chronhub\Storm\Tests\Unit\Projector\Util\ProvideContextWithProphecy;

final class PersistentProjectorRepositoryTest extends ProphecyTestCase
{
    use ProvideContextWithProphecy {
        setUp as contextSetup;
    }

    private Store|ObjectProphecy $store;

    private Chronicler|ObjectProphecy $chronicler;

    protected function setUp(): void
    {
        $this->contextSetup();

        $this->store = $this->prophesize(Store::class);
        $this->chronicler = $this->prophesize(Chronicler::class);
    }

    /**
     * @test
     *
     * @dataProvider provideBoolean
     */
    public function it_rise_projection(bool $exists): void
    {
        $this->store->exists()->willReturn($exists)->shouldBeCalledOnce();

        $exists
            ? $this->store->create()->shouldNotBeCalled()
            : $this->store->create()->willReturn(true)->shouldBeCalledOnce();

        $this->store->acquireLock()->willReturn(true)->shouldBeCalledOnce();

        $this->position->watch(['names' => ['some_stream_name']])->shouldBeCalledOnce();

        $this->store->loadState()->willReturn(true)->shouldBeCalledOnce();

        $context = $this->newContext();
        $repository = $this->persistentRepositoryInstance($context->fromStreams('some_stream_name'));

        $repository->rise();
    }

    /**
     * @test
     */
    public function it_store_projection(): void
    {
        $this->store->persist()->willReturn(true)->shouldBeCalledOnce();

        $context = $this->newContext();

        $repository = $this->persistentRepositoryInstance($context);

        $repository->store();
    }

    /**
     * @test
     */
    public function it_revise_projection(): void
    {
        $context = $this->newContext();
        $context->isStreamCreated = true;

        $this->store->currentStreamName()->willReturn('foo')->shouldBeCalledOnce();
        $this->store->reset()->willReturn(true)->shouldBeCalledOnce();
        $this->chronicler->delete(new StreamName('foo'))->shouldBeCalledOnce();

        $repository = $this->persistentRepositoryInstance($context);

        $repository->revise();

        $this->assertFalse($context->isStreamCreated);
    }

    /**
     * @test
     */
    public function it_revise_projection_and_hold_stream_not_found_exception(): void
    {
        $context = $this->newContext();
        $context->isStreamCreated = true;

        $this->store->currentStreamName()->willReturn('foo')->shouldBeCalledOnce();
        $this->store->reset()->willReturn(true)->shouldBeCalledOnce();

        $streamNotFound = StreamNotFound::withStreamName(new StreamName('foo'));
        $this->chronicler->delete(new StreamName('foo'))->willThrow($streamNotFound)->shouldBeCalledOnce();

        $repository = $this->persistentRepositoryInstance($context);

        $repository->revise();

        $this->assertFalse($context->isStreamCreated);
    }

    /**
     * @test
     *
     * @dataProvider provideBoolean
     */
    public function it_discard_projection_and_hold_stream_not_found_exception(bool $withEmittedEvents): void
    {
        $context = $this->newContext();
        $context->isStreamCreated = true;

        $withEmittedEvents
            ? $this->store->currentStreamName()->willReturn('foo')->shouldBeCalledOnce()
            : $this->store->currentStreamName()->shouldNotBeCalled();

        $this->store->delete($withEmittedEvents)->willReturn(true)->shouldBeCalledOnce();

        $streamNotFound = StreamNotFound::withStreamName(new StreamName('foo'));

        $withEmittedEvents
            ? $this->chronicler->delete(new StreamName('foo'))->willThrow($streamNotFound)->shouldBeCalledOnce()
            : $this->chronicler->delete(new StreamName('foo'))->shouldNotBeCalled();

        $repository = $this->persistentRepositoryInstance($context);

        $repository->discard($withEmittedEvents);

        $withEmittedEvents
            ? $this->assertFalse($context->isStreamCreated)
            : $this->assertTrue($context->isStreamCreated);
    }

    /**
     * @test
     */
    public function it_discard_projection_and_delete_stream_with_emitted_event(): void
    {
        $this->store->currentStreamName()->willReturn('foo')->shouldBeCalledOnce();
        $this->store->delete(true)->willReturn(true)->shouldBeCalledOnce();

        $this->chronicler->delete(new StreamName('foo'))->shouldBeCalledOnce();

        $repository = $this->persistentRepositoryInstance($this->newContext());

        $repository->discard(true);
    }

    /**
     * @test
     */
    public function it_discard_projection_and_does_not_delete_stream(): void
    {
        $this->store->currentStreamName()->shouldNotBeCalled();
        $this->store->delete(false)->willReturn(true)->shouldBeCalledOnce();

        $this->chronicler->delete(new StreamName('foo'))->shouldNotBeCalled();

        $repository = $this->persistentRepositoryInstance($this->newContext());

        $repository->discard(false);
    }

    /**
     * @test
     *
     * @dataProvider provideBoolean
     */
    public function it_bound_state($loaded): void
    {
        $this->store->loadState()->willReturn($loaded)->shouldBeCalledOnce();

        $repository = $this->persistentRepositoryInstance($this->newContext());

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

        $repository = $this->persistentRepositoryInstance($this->newContext());

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

        $repository = $this->persistentRepositoryInstance($this->newContext());

        $repository->restart();
    }

    /**
     * @test
     */
    public function it_disclose_status_projection(): void
    {
        $this->store->loadStatus()->willReturn(ProjectionStatus::RUNNING)->shouldBeCalledOnce();

        $repository = $this->persistentRepositoryInstance($this->newContext());

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

        $repository = $this->persistentRepositoryInstance($this->newContext());

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

        $repository = $this->persistentRepositoryInstance($this->newContext());

        $repository->freed();
    }

    /**
     * @test
     */
    public function it_return_stream_name(): void
    {
        $this->store->currentStreamName()->willReturn('foo')->shouldBeCalledOnce();

        $repository = $this->persistentRepositoryInstance($this->newContext());

        $this->assertEquals('foo', $repository->streamName());
    }

    private function persistentRepositoryInstance(Context $context): PersistentProjectorRepository
    {
        return new PersistentProjectorRepository(
            $context, $this->store->reveal(), $this->chronicler->reveal()
        );
    }

    public function provideBoolean(): Generator
    {
        yield [false];
        yield [true];
    }
}
