<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Generator;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Chronhub\Storm\Projector\Scheme\Context;
use PHPUnit\Framework\MockObject\MockObject;
use Chronhub\Storm\Contracts\Projector\Store;
use Chronhub\Storm\Projector\ProjectionStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Tests\Unit\Projector\Util\ProvideMockContext;
use Chronhub\Storm\Projector\Repository\PersistentProjectorRepository;

final class PersistentProjectorRepositoryTest extends UnitTestCase
{
    use ProvideMockContext {
        setUp as contextSetup;
    }

    private Store|MockObject $store;

    private Chronicler|MockObject $chronicler;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->contextSetup();

        $this->store = $this->createMock(Store::class);
        $this->chronicler = $this->createMock(Chronicler::class);
    }

    #[DataProvider('provideBoolean')]
    #[Test]
    public function it_rise_projection(bool $exists): void
    {
        $this->store->expects($this->once())->method('exists')->willReturn($exists);

        $exists
            ? $this->store->expects($this->never())->method('create')
            : $this->store->expects($this->once())->method('create')->willReturn(true);

        $this->store->expects($this->once())->method('acquireLock')->willReturn(true);
        $this->position->expects($this->once())->method('watch')->with(['names' => ['some_stream_name']]);
        $this->store->expects($this->once())->method('loadState')->willReturn(true);

        $context = $this->newContext();
        $repository = $this->persistentRepositoryInstance($context->fromStreams('some_stream_name'));

        $repository->rise();
    }

    #[Test]
    public function it_store_projection(): void
    {
        $this->store->expects($this->once())->method('persist')->willReturn(true);

        $context = $this->newContext();

        $repository = $this->persistentRepositoryInstance($context);

        $repository->store();
    }

    #[Test]
    public function it_revise_projection(): void
    {
        $context = $this->newContext();
        $context->isStreamCreated = true;

        $this->store->expects($this->once())->method('currentStreamName')->willReturn('foo');
        $this->store->expects($this->once())->method('reset')->willReturn(true);

        $this->chronicler->expects($this->once())->method('delete')->with(new StreamName('foo'));

        $repository = $this->persistentRepositoryInstance($context);

        $repository->revise();

        $this->assertFalse($context->isStreamCreated);
    }

    #[Test]
    public function it_revise_projection_and_hold_stream_not_found_exception(): void
    {
        $context = $this->newContext();
        $context->isStreamCreated = true;

        $this->store->expects($this->once())->method('currentStreamName')->willReturn('foo');
        $this->store->expects($this->once())->method('reset')->willReturn(true);

        $streamNotFound = StreamNotFound::withStreamName(new StreamName('foo'));

        $this->chronicler->expects($this->once())->method('delete')->with(new StreamName('foo'))->willThrowException($streamNotFound);

        $repository = $this->persistentRepositoryInstance($context);

        $repository->revise();

        $this->assertFalse($context->isStreamCreated);
    }

    #[DataProvider('provideBoolean')]
    #[Test]
    public function it_discard_projection_and_hold_stream_not_found_exception(bool $withEmittedEvents): void
    {
        $context = $this->newContext();
        $context->isStreamCreated = true;

        $withEmittedEvents
            ? $this->store->expects($this->once())->method('currentStreamName')->willReturn('foo')
            : $this->store->expects($this->never())->method('currentStreamName');

        $this->store->expects($this->once())->method('delete')->with($withEmittedEvents)->willReturn(true);

        $streamNotFound = StreamNotFound::withStreamName(new StreamName('foo'));

        $withEmittedEvents
            ? $this->chronicler->expects($this->once())->method('delete')->with(new StreamName('foo'))->willThrowException($streamNotFound)
            : $this->chronicler->expects($this->never())->method('delete');

        $repository = $this->persistentRepositoryInstance($context);

        $repository->discard($withEmittedEvents);

        $withEmittedEvents
            ? $this->assertFalse($context->isStreamCreated)
            : $this->assertTrue($context->isStreamCreated);
    }

    #[Test]
    public function it_discard_projection_and_delete_stream_with_emitted_event(): void
    {
        $this->store->expects($this->once())->method('currentStreamName')->willReturn('foo');
        $this->store->expects($this->once())->method('delete')->with(true)->willReturn(true);

        $this->chronicler->expects($this->once())->method('delete')->with(new StreamName('foo'));

        $repository = $this->persistentRepositoryInstance($this->newContext());

        $repository->discard(true);
    }

    #[Test]
    public function it_discard_projection_and_does_not_delete_stream(): void
    {
        $this->store->expects($this->never())->method('currentStreamName');
        $this->store->expects($this->once())->method('delete')->with(false)->willReturn(true);
        $this->chronicler->expects($this->never())->method('delete');

        $repository = $this->persistentRepositoryInstance($this->newContext());

        $repository->discard(false);
    }

    #[DataProvider('provideBoolean')]
    #[Test]
    public function it_bound_state($loaded): void
    {
        $this->store->expects($this->once())->method('loadState')->willReturn($loaded);

        $repository = $this->persistentRepositoryInstance($this->newContext());

        $repository->boundState();
    }

    #[DataProvider('provideBoolean')]
    #[Test]
    public function it_close_projection($closed): void
    {
        $this->store->expects($this->once())->method('stop')->willReturn($closed);

        $repository = $this->persistentRepositoryInstance($this->newContext());

        $repository->close();
    }

    #[DataProvider('provideBoolean')]
    #[Test]
    public function it_restart_projection($restarted): void
    {
        $this->store->expects($this->once())->method('startAgain')->willReturn($restarted);

        $repository = $this->persistentRepositoryInstance($this->newContext());

        $repository->restart();
    }

    #[Test]
    public function it_disclose_status_projection(): void
    {
        $this->store->expects($this->once())->method('loadStatus')->willReturn(ProjectionStatus::RUNNING);

        $repository = $this->persistentRepositoryInstance($this->newContext());

        $this->assertEquals(ProjectionStatus::RUNNING, $repository->disclose());
    }

    #[DataProvider('provideBoolean')]
    #[Test]
    public function it_renew_projection_lock($updated): void
    {
        $this->store->expects($this->once())->method('updateLock')->willReturn($updated);

        $repository = $this->persistentRepositoryInstance($this->newContext());

        $repository->renew();
    }

    #[DataProvider('provideBoolean')]
    #[Test]
    public function it_freed_projection_lock(bool $unlocked): void
    {
        $this->store->expects($this->once())->method('releaseLock')->willReturn($unlocked);

        $repository = $this->persistentRepositoryInstance($this->newContext());

        $repository->freed();
    }

    #[Test]
    public function it_return_stream_name(): void
    {
        $this->store->expects($this->once())->method('currentStreamName')->willReturn('foo');

        $repository = $this->persistentRepositoryInstance($this->newContext());

        $this->assertEquals('foo', $repository->streamName());
    }

    private function persistentRepositoryInstance(Context $context): PersistentProjectorRepository
    {
        return new PersistentProjectorRepository($context, $this->store, $this->chronicler);
    }

    public static function provideBoolean(): Generator
    {
        yield [false];
        yield [true];
    }
}
