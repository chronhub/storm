<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Closure;
use Generator;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Projector\ProjectionStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use Chronhub\Storm\Projector\Pipes\PreparePersistentRunner;
use Chronhub\Storm\Tests\Unit\Projector\Util\ProvideMockContext;

final class PreparePersistentRunnerTest extends UnitTestCase
{
    use ProvideMockContext;

    #[DataProvider('provideBoolean')]
    #[Test]
    public function it_initiate_projection_when_loading_status_from_remote(bool $runInBackground): void
    {
        $status = ProjectionStatus::RUNNING;

        $this->repository->expects($this->once())->method('disclose')->willReturn($status);
        $this->repository->expects($this->once())->method('rise');

        $context = $this->newContext();

        $context->runner->runInBackground($runInBackground);

        $pipe = new PreparePersistentRunner($this->repository);

        $this->assertIsInitialized($pipe, false);

        $run = $pipe($context, fn (Context $context): bool => true);

        $this->assertTrue($run);
        $this->assertIsInitialized($pipe, true);
    }

    #[DataProvider('provideBoolean')]
    #[Test]
    public function it_stop_projection_when_remote_status_is_stopping(bool $runInBackground): void
    {
        $status = ProjectionStatus::STOPPING;

        $this->repository->expects($this->once())->method('disclose')->willReturn($status);
        $this->repository->expects($this->once())->method('boundState');
        $this->repository->expects($this->once())->method('close');
        $this->repository->expects($this->never())->method('rise');

        $context = $this->newContext();
        $context->runner->runInBackground($runInBackground);

        $pipe = new PreparePersistentRunner($this->repository);

        $this->assertIsInitialized($pipe, false);

        $run = $pipe($context, fn (Context $context): bool => true);

        $this->assertTrue($run);

        $this->assertIsInitialized($pipe, true);
    }

    #[DataProvider('provideBoolean')]
    #[Test]
    public function it_stop_projection_when_remote_status_is_resetting(bool $runInBackground): void
    {
        $status = ProjectionStatus::RESETTING;

        $this->repository->expects($this->once())->method('disclose')->willReturn($status);
        $this->repository->expects($this->once())->method('revise');
        $this->repository->expects($this->never())->method('restart');
        $this->repository->expects($this->once())->method('rise');

        $context = $this->newContext();
        $context->runner->runInBackground($runInBackground);

        $pipe = new PreparePersistentRunner($this->repository);

        $this->assertIsInitialized($pipe, false);

        $run = $pipe($context, fn (Context $context): bool => true);

        $this->assertTrue($run);

        $this->assertIsInitialized($pipe, true);
    }

    #[DataProvider('provideBoolean')]
    #[Test]
    public function it_initiate_projection_when_remote_status_is_deleting(bool $runInBackground): void
    {
        $status = ProjectionStatus::DELETING;

        $this->repository->expects($this->once())->method('disclose')->willReturn($status);
        $this->repository->expects($this->once())->method('discard')->with(false);
        $this->repository->expects($this->never())->method('restart');
        $this->repository->expects($this->never())->method('rise');

        $context = $this->newContext();

        $context->runner->runInBackground($runInBackground);

        $pipe = new PreparePersistentRunner($this->repository);

        $this->assertIsInitialized($pipe, false);

        $run = $pipe($context, fn (Context $context): bool => true);

        $this->assertTrue($run);

        $this->assertIsInitialized($pipe, true);
    }

    #[DataProvider('provideBoolean')]
    #[Test]
    public function it_initiate_projection_when_remote_status_is_deleting_with_emitted_events(bool $runInBackground): void
    {
        $status = ProjectionStatus::DELETING_WITH_EMITTED_EVENTS;

        $this->repository->expects($this->once())->method('disclose')->willReturn($status);
        $this->repository->expects($this->once())->method('discard')->with(true);
        $this->repository->expects($this->never())->method('restart');
        $this->repository->expects($this->never())->method('rise');

        $context = $this->newContext();

        $context->runner->runInBackground($runInBackground);

        $pipe = new PreparePersistentRunner($this->repository);

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

    public static function provideBoolean(): Generator
    {
        yield [true];
        yield [false];
    }
}
