<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Generator;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Chronhub\Storm\Tests\ProphecyTestCase;
use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Contracts\Projector\ProjectionModel;
use Chronhub\Storm\Contracts\Serializer\JsonSerializer;
use Chronhub\Storm\Projector\Repository\RepositoryLock;
use Chronhub\Storm\Projector\Repository\StandaloneStore;
use Chronhub\Storm\Contracts\Projector\ProjectionProvider;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;
use Chronhub\Storm\Tests\Unit\Projector\Util\ProvideContextWithProphecy;

final class StandaloneStoreTest extends ProphecyTestCase
{
    use ProvideContextWithProphecy {
        setUp as contextSetUp;
    }

    private ProjectionProvider|ObjectProphecy $projectionProvider;

    private RepositoryLock|ObjectProphecy $projectorLock;

    private ObjectProphecy|JsonSerializer $jsonSerializer;

    private string $streamName;

    protected function setUp(): void
    {
        $this->contextSetUp();

        $this->projectionProvider = $this->prophesize(ProjectionProvider::class);
        $this->projectorLock = $this->prophesize(RepositoryLock::class);
        $this->jsonSerializer = $this->prophesize(JsonSerializer::class);
        $this->streamName = 'customer';
    }

    /**
     * @test
     */
    public function it_create_projection(): void
    {
        $this->projectionProvider
            ->createProjection($this->streamName, 'idle')
            ->willReturn(true)
            ->shouldBeCalledOnce();

        $context = $this->newContext();
        $store = $this->standaloneProjectionInstance($context);

        $store->create();
    }

    /**
     * @test
     */
    public function it_load_projection_state(): void
    {
        $this->position->discover([$this->streamName => 5])->shouldBeCalledOnce();

        $model = $this->prophesize(ProjectionModel::class);
        $model->position()->willReturn('{"customer":5}')->shouldBeCalledOnce();
        $model->state()->willReturn('{"count":5}')->shouldBeCalledOnce();

        $this->jsonSerializer->decode('{"customer":5}')->willReturn(['customer' => 5])->shouldBeCalledOnce();
        $this->jsonSerializer->decode('{"count":5}')->willReturn(['count' => 5])->shouldBeCalledOnce();

        $this->projectionProvider
            ->retrieve($this->streamName)
            ->willReturn($model->reveal())
            ->shouldBeCalledOnce();

        $context = $this->newContext();
        $store = $this->standaloneProjectionInstance($context);

        $this->assertTrue($store->loadState());

        $this->assertEquals(['count' => 5], $context->state->get());
    }

    /**
     * @test
     */
    public function it_raise_exception_when_projection_name_not_found_on_load_projection_state(): void
    {
        $this->expectException(ProjectionNotFound::class);
        $this->expectExceptionMessage("Projection name $this->streamName not found");

        $this->position->discover([])->shouldNotBeCalled();

        $this->projectionProvider
            ->retrieve($this->streamName)
            ->willReturn(null)
            ->shouldBeCalledOnce();

        $context = $this->newContext();

        $store = $this->standaloneProjectionInstance($context);

        $store->loadState();
    }

    /**
     * @test
     *
     * @dataProvider provideBoolean
     */
    public function it_acquire_lock(bool $acquireLock): void
    {
        $this->projectorLock->acquire()->willReturn('some_lock_time')->shouldBeCalledOnce();
        $this->projectorLock->current()->willReturn('last_lock_update')->shouldBeCalledOnce();

        $this->projectionProvider->acquireLock(
            $this->streamName,
            ProjectionStatus::RUNNING->value,
            'some_lock_time',
            'last_lock_update'
        )->willReturn($acquireLock)->shouldBeCalledOnce();

        $context = $this->newContext();

        $context->status = ProjectionStatus::IDLE;

        $store = $this->standaloneProjectionInstance($context);

        $acquired = $store->acquireLock();

        $this->assertEquals($acquireLock, $acquired);

        $acquireLock
            ? $this->assertEquals(ProjectionStatus::RUNNING, $context->status)
            : $this->assertEquals(ProjectionStatus::IDLE, $context->status);
    }

    /**
     * @test
     */
    public function it_update_lock(): void
    {
        $this->projectorLock->tryUpdate()->willReturn(false)->shouldBeCalledOnce();

        $context = $this->newContext();

        $store = $this->standaloneProjectionInstance($context);

        $this->assertTrue($store->updateLock());
    }

    /**
     * @test
     *
     * @dataProvider provideBoolean
     */
    public function it_update_lock_if_succeeded(bool $updated): void
    {
        $this->projectorLock->tryUpdate()->willReturn(true)->shouldBeCalledOnce();
        $this->projectorLock->update()->willReturn('current_lock')->shouldBeCalledOnce();
        $this->position->all()->willReturn(['customer' => 5])->shouldBeCalledOnce();

        $this->jsonSerializer->encode(['customer' => 5])->willReturn('{"customer":5}')->shouldBeCalledOnce();

        $this->projectionProvider->updateProjection($this->streamName, [
            'locked_until' => 'current_lock',
            'position' => '{"customer":5}',
        ])->willReturn($updated)->shouldBeCalledOnce();

        $context = $this->newContext();

        $store = $this->standaloneProjectionInstance($context);

        $this->assertEquals($updated, $store->updateLock());
    }

    /**
     * @test
     *
     * @dataProvider provideBoolean
     */
    public function it_release_lock(bool $released): void
    {
        $this->projectionProvider->updateProjection(
            $this->streamName, [
                'status' => ProjectionStatus::IDLE->value,
                'locked_until' => null,
            ]
        )->willReturn($released)->shouldBeCalledOnce();

        $context = $this->newContext();

        $context->status = ProjectionStatus::RUNNING;

        $store = $this->standaloneProjectionInstance($context);

        $releasedLock = $store->releaseLock();

        $this->assertEquals($releasedLock, $released);

        $releasedLock
            ? $this->assertEquals(ProjectionStatus::IDLE, $context->status)
            : $this->assertEquals(ProjectionStatus::RUNNING, $context->status);
    }

    /**
     * @test
     *
     * @dataProvider provideBoolean
     */
    public function it_assert_projection_exists(bool $exists): void
    {
        $this->projectionProvider->projectionExists($this->streamName)->willReturn($exists)->shouldBeCalledOnce();

        $context = $this->newContext();

        $store = $this->standaloneProjectionInstance($context);

        $this->assertEquals($exists, $store->exists());
    }

    /**
     * @test
     */
    public function it_stop_projection(): void
    {
        $context = $this->newContext();
        $context->runner->stop(false);
        $context->status = ProjectionStatus::RUNNING;

        $context->state->put(['count' => 5]);
        $this->projectorLock->refresh()->willReturn('time_with_milliseconds')->shouldBeCalledOnce();
        $this->position->all()->willReturn(['customer' => 5])->shouldBeCalledOnce();
        $this->jsonSerializer->encode(['customer' => 5])->willReturn('{"customer":5}')->shouldBeCalledOnce();
        $this->jsonSerializer->encode(['count' => 5])->willReturn('{"count":5}')->shouldBeCalledOnce();

        $this->projectionProvider->updateProjection($this->streamName, Argument::type('array'));

        $this->projectionProvider->updateProjection($this->streamName, [
            'status' => ProjectionStatus::IDLE->value,
        ])->willReturn(true)->shouldBeCalledOnce();

        $store = $this->standaloneProjectionInstance($context);
        $this->assertTrue($store->stop());

        $this->assertTrue($context->runner->isStopped());
        $this->assertEquals(ProjectionStatus::IDLE, $context->status);
    }

    /**
     * @test
     */
    public function it_fails_stop_projection(): void
    {
        $context = $this->newContext();
        $context->runner->stop(false);
        $context->status = ProjectionStatus::RUNNING;

        $context->state->put(['count' => 5]);
        $this->projectorLock->refresh()->willReturn('time_with_milliseconds')->shouldBeCalledOnce();
        $this->position->all()->willReturn(['customer' => 5])->shouldBeCalledOnce();
        $this->jsonSerializer->encode(['customer' => 5])->willReturn('{"customer":5}')->shouldBeCalledOnce();
        $this->jsonSerializer->encode(['count' => 5])->willReturn('{"count":5}')->shouldBeCalledOnce();

        $this->projectionProvider->updateProjection($this->streamName, Argument::type('array'));

        $this->projectionProvider->updateProjection($this->streamName, [
            'status' => ProjectionStatus::IDLE->value,
        ])->willReturn(false)->shouldBeCalledOnce();

        $store = $this->standaloneProjectionInstance($context);
        $this->assertFalse($store->stop());

        $this->assertTrue($context->runner->isStopped());
        $this->assertEquals(ProjectionStatus::RUNNING, $context->status);
    }

    /**
     * @test
     */
    public function it_start_again_projection(): void
    {
        $context = $this->newContext();
        $context->runner->stop(true);
        $context->status = ProjectionStatus::STOPPING;

        $this->projectorLock->acquire()->willReturn('some_time_in_ms')->shouldBeCalledOnce();

        $this->projectionProvider->updateProjection($this->streamName, [
            'status' => ProjectionStatus::RUNNING->value,
            'locked_until' => 'some_time_in_ms',
        ])->willReturn(true)->shouldBeCalledOnce();

        $store = $this->standaloneProjectionInstance($context);
        $this->assertTrue($store->startAgain());

        $this->assertFalse($context->runner->isStopped());
        $this->assertEquals(ProjectionStatus::RUNNING, $context->status);
    }

    /**
     * @test
     */
    public function it_fails_start_again_projection(): void
    {
        $context = $this->newContext();
        $context->runner->stop(true);
        $context->status = ProjectionStatus::STOPPING;

        $this->projectorLock->acquire()->willReturn('some_time_in_ms')->shouldBeCalledOnce();

        $this->projectionProvider->updateProjection($this->streamName, [
            'status' => ProjectionStatus::RUNNING->value,
            'locked_until' => 'some_time_in_ms',
        ])->willReturn(false)->shouldBeCalledOnce();

        $store = $this->standaloneProjectionInstance($context);
        $this->assertFalse($store->startAgain());

        $this->assertFalse($context->runner->isStopped());
        $this->assertEquals(ProjectionStatus::STOPPING, $context->status);
    }

    /**
     * @test
     */
    public function it_reset_projection(): void
    {
        $context = $this->newContext();
        $context->state->put(['foo' => 'bar']);
        $context->initialize(fn (): array => ['count' => 0]);
        $context->status = ProjectionStatus::RESETTING;

        $this->position->reset()->shouldBeCalledOnce();
        $this->position->all()->willReturn(['customer' => 5])->shouldBeCalledOnce();

        $this->jsonSerializer->encode(['customer' => 5])->willReturn('{"customer":5}')->shouldBeCalledOnce();
        $this->jsonSerializer->encode(['count' => 0])->willReturn('{"count":0}')->shouldBeCalledOnce();

        $this->projectionProvider->updateProjection($this->streamName, [
            'position' => '{"customer":5}',
            'state' => '{"count":0}',
            'status' => ProjectionStatus::RESETTING->value,
        ])->willReturn(true)->shouldBeCalledOnce();

        $store = $this->standaloneProjectionInstance($context);

        $this->assertTrue($store->reset());
        $this->assertEquals(['count' => 0], $context->state->get());
    }

    /**
     * @test
     */
    public function it_fails_reset_projection(): void
    {
        $context = $this->newContext();
        $context->state->put(['foo' => 'bar']);
        $context->initialize(fn (): array => ['count' => 0]);
        $context->status = ProjectionStatus::RESETTING;

        $this->position->reset()->shouldBeCalledOnce();
        $this->position->all()->willReturn(['customer' => 5])->shouldBeCalledOnce();

        $this->jsonSerializer->encode(['customer' => 5])->willReturn('{"customer":5}')->shouldBeCalledOnce();
        $this->jsonSerializer->encode(['count' => 0])->willReturn('{"count":0}')->shouldBeCalledOnce();

        $this->projectionProvider->updateProjection($this->streamName, [
            'position' => '{"customer":5}',
            'state' => '{"count":0}',
            'status' => ProjectionStatus::RESETTING->value,
        ])->willReturn(false)->shouldBeCalledOnce();

        $store = $this->standaloneProjectionInstance($context);
        $this->assertFalse($store->reset());

        $this->assertEquals(['count' => 0], $context->state->get());
    }

    /**
     * @test
     *
     * @dataProvider provideBoolean
     */
    public function it_delete_projection(bool $withEmittedEvent): void
    {
        $context = $this->newContext();

        $this->assertFalse($context->runner->isStopped());

        $context->state->put(['foo' => 'bar']);
        $context->initialize(fn (): array => ['count' => 0]);

        $this->position->reset()->shouldBeCalledOnce();

        $this->projectionProvider->deleteProjection($this->streamName)->willReturn(true)->shouldBeCalledOnce();

        $store = $this->standaloneProjectionInstance($context);

        $this->assertTrue($store->delete($withEmittedEvent));
        $this->assertEquals(['count' => 0], $context->state->get());
        $this->assertTrue($context->runner->isStopped());
    }

    /**
     * @test
     *
     * @dataProvider provideBoolean
     */
    public function it_fails_delete_projection(bool $withEmittedEvent): void
    {
        $context = $this->newContext();

        $context->state->put(['foo' => 'bar']);
        $context->initialize(fn (): array => ['count' => 0]);

        $this->position->reset()->shouldNotBeCalled();

        $this->projectionProvider->deleteProjection($this->streamName)->willReturn(false)->shouldBeCalledOnce();

        $store = $this->standaloneProjectionInstance($context);

        $this->assertFalse($store->delete($withEmittedEvent));
        $this->assertEquals(['foo' => 'bar'], $context->state->get());
    }

    /**
     * @test
     */
    public function it_load_projection_status_and_return_running_status_when_projection_model_not_found(): void
    {
        $context = $this->newContext();

        $this->projectionProvider->retrieve($this->streamName)->willReturn(null);

        $store = $this->standaloneProjectionInstance($context);

        $this->assertEquals(ProjectionStatus::RUNNING, $store->loadStatus());
    }

    /**
     * @test
     *
     * @dataProvider provideProjectionStatus
     */
    public function it_load_projection_status_and_return_status_from_projection_model(ProjectionStatus $projectionStatus): void
    {
        $projectionModel = $this->prophesize(ProjectionModel::class);
        $projectionModel->status()->willReturn($projectionStatus->value)->shouldBeCalledOnce();

        $context = $this->newContext();

        $this->projectionProvider->retrieve($this->streamName)->willReturn($projectionModel->reveal());

        $store = $this->standaloneProjectionInstance($context);

        $this->assertEquals($projectionStatus, $store->loadStatus());
    }

    /**
     * @test
     */
    public function it_access_current_stream_name(): void
    {
        $context = $this->newContext();

        $store = $this->standaloneProjectionInstance($context);

        $this->assertEquals($this->streamName, $store->currentStreamName());
    }

    private function standaloneProjectionInstance(Context $context): StandaloneStore
    {
        return new StandaloneStore(
            $context,
            $this->projectionProvider->reveal(),
            $this->projectorLock->reveal(),
            $this->jsonSerializer->reveal(),
            $this->streamName
        );
    }

    public function provideBoolean(): Generator
    {
        yield [true];
        yield [false];
    }

    public function provideProjectionStatus(): Generator
    {
        yield [ProjectionStatus::RUNNING];
        yield [ProjectionStatus::IDLE];
        yield [ProjectionStatus::STOPPING];
        yield [ProjectionStatus::RESETTING];
        yield [ProjectionStatus::DELETING];
        yield [ProjectionStatus::DELETING_WITH_EMITTED_EVENTS];
    }
}
