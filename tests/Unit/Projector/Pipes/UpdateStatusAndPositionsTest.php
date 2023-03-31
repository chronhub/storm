<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Pipes;

use Generator;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Projector\Scheme\Context;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Projector\ProjectionStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use Chronhub\Storm\Projector\Activity\UpdateStatusAndPositions;
use Chronhub\Storm\Tests\Unit\Projector\Mock\ProvideMockContext;

#[CoversClass(UpdateStatusAndPositions::class)]
final class UpdateStatusAndPositionsTest extends UnitTestCase
{
    use ProvideMockContext;

    #[DataProvider('provideBoolean')]
    #[Test]
    public function it_reload_remote_status(bool $runInBackground): void
    {
        $status = ProjectionStatus::IDLE;

        $this->repository->expects($this->once())
            ->method('disclose')
            ->willReturn($status);

        $context = $this->newContext();
        $context->fromStreams('add');
        $context->runner->runInBackground($runInBackground);

        $this->position->expects($this->once())
            ->method('watch')
            ->with(['names' => ['add']]);

        $pipe = new UpdateStatusAndPositions($this->repository);

        $run = $pipe($context, fn (Context $context): bool => true);

        $this->assertTrue($run);
    }

    #[DataProvider('provideBoolean')]
    #[Test]
    public function it_stop_on_stop_status_loaded(bool $runInBackground): void
    {
        $status = ProjectionStatus::STOPPING;

        $this->repository->expects($this->once())
            ->method('disclose')
            ->willReturn($status);

        $this->repository->expects($this->never())->method('boundState');

        $this->repository->expects($this->once())->method('close');

        $context = $this->newContext();

        $context->fromStreams('add');

        $context->runner->runInBackground($runInBackground);

        $this->position->expects($this->once())
            ->method('watch')
            ->with(['names' => ['add']]);

        $pipe = new UpdateStatusAndPositions($this->repository);

        $run = $pipe($context, fn (Context $context): bool => true);

        $this->assertTrue($run);
    }

    #[DataProvider('provideBoolean')]
    #[Test]
    public function it_reset_on_resetting_status_loaded(bool $runInBackground): void
    {
        $status = ProjectionStatus::RESETTING;

        $this->repository->expects($this->once())->method('disclose')->willReturn($status);
        $this->repository->expects($this->once())->method('revise');

        $runInBackground
            ? $this->repository->expects($this->once())->method('restart')
            : $this->repository->expects($this->never())->method('restart');

        $context = $this->newContext();

        $context->fromStreams('add');
        $context->runner->runInBackground($runInBackground);

        $this->position->expects($this->once())
            ->method('watch')
            ->with(['names' => ['add']]);

        $pipe = new UpdateStatusAndPositions($this->repository);

        $run = $pipe($context, fn (Context $context): bool => true);

        $this->assertTrue($run);
    }

    #[DataProvider('provideBoolean')]
    #[Test]
    public function it_delete_on_deleting_status_loaded(bool $runInBackground): void
    {
        $status = ProjectionStatus::DELETING;

        $this->repository->expects($this->once())->method('disclose')->willReturn($status);
        $this->repository->expects($this->once())->method('discard');

        $context = $this->newContext();

        $context->runner->runInBackground($runInBackground);

        $context->fromStreams('add');

        $this->position->expects($this->once())
            ->method('watch')
            ->with(['names' => ['add']]);

        $pipe = new UpdateStatusAndPositions($this->repository);

        $run = $pipe($context, fn (Context $context): bool => true);

        $this->assertTrue($run);
    }

    #[DataProvider('provideBoolean')]
    #[Test]
    public function it_delete_with_events_on_deleting_with_emitted_events_status_loaded(bool $runInBackground): void
    {
        $status = ProjectionStatus::DELETING_WITH_EMITTED_EVENTS;

        $this->repository->expects($this->once())->method('disclose')->willReturn($status);
        $this->repository->expects($this->once())->method('discard');

        $context = $this->newContext();

        $context->fromStreams('add');

        $context->runner->runInBackground($runInBackground);

        $this->position->expects($this->once())->method('watch')->with(['names' => ['add']]);

        $pipe = new UpdateStatusAndPositions($this->repository);

        $run = $pipe($context, fn (Context $context): bool => true);

        $this->assertTrue($run);
    }

    public static function provideBoolean(): Generator
    {
        yield [true];

        yield [false];
    }
}
