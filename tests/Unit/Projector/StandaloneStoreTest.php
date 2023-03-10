<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector;

use Generator;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Chronhub\Storm\Projector\Scheme\Context;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Projector\ProjectionStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use Chronhub\Storm\Contracts\Projector\ProjectionModel;
use Chronhub\Storm\Contracts\Serializer\JsonSerializer;
use Chronhub\Storm\Projector\Repository\RepositoryLock;
use Chronhub\Storm\Projector\Repository\StandaloneStore;
use Chronhub\Storm\Contracts\Projector\ProjectionProvider;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;
use Chronhub\Storm\Tests\Unit\Projector\Util\ProvideMockContext;

// todo fix test willReturnMap
#[CoversClass(StandaloneStore::class)]
final class StandaloneStoreTest extends UnitTestCase
{
    use ProvideMockContext {
        setUp as contextSetUp;
    }

    private ProjectionProvider|MockObject $projectionProvider;

    private RepositoryLock|MockObject $projectorLock;

    private JsonSerializer|MockObject $jsonSerializer;

    private string $streamName;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->contextSetUp();

        $this->projectionProvider = $this->createMock(ProjectionProvider::class);
        $this->projectorLock = $this->createMock(RepositoryLock::class);
        $this->jsonSerializer = $this->createMock(JsonSerializer::class);
        $this->streamName = 'customer';
    }

    #[Test]
    public function it_create_projection(): void
    {
        $this->projectionProvider->expects($this->once())
            ->method('createProjection')
            ->with($this->streamName, 'idle')
            ->willReturn(true);

        $context = $this->newContext();
        $store = $this->standaloneProjectionInstance($context);

        $store->create();
    }

    #[Test]
    public function it_load_projection_state(): void
    {
        $this->position->expects($this->once())
            ->method('discover')
            ->with([$this->streamName => 5]);

        $model = $this->createMock(ProjectionModel::class);
        $model->expects($this->once())->method('position')->willReturn('{"customer":5}');
        $model->expects($this->once())->method('state')->willReturn('{"count":5}');

        $this->jsonSerializer->expects($this->any())
            ->method('decode')
            ->willReturnMap([
                ['{"customer":5}', ['customer' => 5]],
                ['{"count":5}', ['count' => 5]],
            ]);

        $this->projectionProvider->expects($this->once())
            ->method('retrieve')
            ->with($this->streamName)
            ->willReturn($model);

        $context = $this->newContext();
        $store = $this->standaloneProjectionInstance($context);

        $this->assertTrue($store->loadState());

        $this->assertEquals(['count' => 5], $context->state->get());
    }

    #[Test]
    public function it_raise_exception_when_projection_name_not_found_on_load_projection_state(): void
    {
        $this->expectException(ProjectionNotFound::class);
        $this->expectExceptionMessage("Projection name $this->streamName not found");

        $this->position->expects($this->never())->method('discover');

        $this->projectionProvider->expects($this->once())
            ->method('retrieve')
            ->with($this->streamName)
            ->willReturn(null);

        $context = $this->newContext();

        $store = $this->standaloneProjectionInstance($context);

        $store->loadState();
    }

    #[DataProvider('provideBoolean')]
    #[Test]
    public function it_acquire_lock(bool $acquireLock): void
    {
        $this->projectorLock->expects($this->once())
            ->method('acquire')
            ->willReturn('some_lock_time');

        $this->projectorLock->expects($this->once())
            ->method('current')
            ->willReturn('last_lock_update');

        $this->projectionProvider->expects($this->once())
            ->method('acquireLock')
            ->with($this->streamName, ProjectionStatus::RUNNING->value, 'some_lock_time', 'last_lock_update')
            ->willReturn($acquireLock);

        $context = $this->newContext();

        $context->status = ProjectionStatus::IDLE;

        $store = $this->standaloneProjectionInstance($context);

        $acquired = $store->acquireLock();

        $this->assertEquals($acquireLock, $acquired);

        $acquireLock
            ? $this->assertEquals(ProjectionStatus::RUNNING, $context->status)
            : $this->assertEquals(ProjectionStatus::IDLE, $context->status);
    }

    #[Test]
    public function it_update_lock(): void
    {
        $this->projectorLock->expects($this->once())
            ->method('tryUpdate')
            ->willReturn(false);

        $context = $this->newContext();

        $store = $this->standaloneProjectionInstance($context);

        $this->assertTrue($store->updateLock());
    }

    #[DataProvider('provideBoolean')]
    #[Test]
    public function it_update_lock_if_succeeded(bool $updated): void
    {
        $this->projectorLock->expects($this->once())
            ->method('tryUpdate')
            ->willReturn(true);

        $this->projectorLock->expects($this->once())
            ->method('update')
            ->willReturn('current_lock');

        $this->position->expects($this->once())
            ->method('all')
            ->willReturn(['customer' => 5]);

        $this->jsonSerializer->expects($this->once())
            ->method('encode')
            ->with(['customer' => 5])
            ->willReturn('{"customer":5}');

        $this->projectionProvider->expects($this->once())
            ->method('updateProjection')
            ->with($this->streamName, [
                'locked_until' => 'current_lock',
                'position' => '{"customer":5}',
            ])
            ->willReturn($updated);

        $context = $this->newContext();

        $store = $this->standaloneProjectionInstance($context);

        $this->assertEquals($updated, $store->updateLock());
    }

    #[DataProvider('provideBoolean')]
    #[Test]
    public function it_release_lock(bool $released): void
    {
        $this->projectionProvider->expects($this->once())
            ->method('updateProjection')
            ->with($this->streamName, [
                'status' => ProjectionStatus::IDLE->value,
                'locked_until' => null,
            ])
            ->willReturn($released);

        $context = $this->newContext();

        $context->status = ProjectionStatus::RUNNING;

        $store = $this->standaloneProjectionInstance($context);

        $releasedLock = $store->releaseLock();

        $this->assertEquals($releasedLock, $released);

        $releasedLock
            ? $this->assertEquals(ProjectionStatus::IDLE, $context->status)
            : $this->assertEquals(ProjectionStatus::RUNNING, $context->status);
    }

    #[DataProvider('provideBoolean')]
    #[Test]
    public function it_assert_projection_exists(bool $exists): void
    {
        $this->projectionProvider->expects($this->once())
            ->method('projectionExists')
            ->with($this->streamName)
            ->willReturn($exists);

        $context = $this->newContext();

        $store = $this->standaloneProjectionInstance($context);

        $this->assertEquals($exists, $store->exists());
    }

    #[Test]
    public function it_stop_projection(): void
    {
        $context = $this->newContext();

        $context->runner->stop(false);
        $context->status = ProjectionStatus::RUNNING;
        $context->state->put(['count' => 5]);

        $this->projectorLock->expects($this->once())
            ->method('refresh')
            ->willReturn('time');

        $this->position->expects($this->once())
            ->method('all')
            ->willReturn(['customer' => 5]);

        $this->jsonSerializer->expects($this->exactly(2))
            ->method('encode');
//            ->willReturnMap(
//                [
//                    [['customer' => 5], '{"customer":5}'],
//                    [['count' => 5], '{"count":5}'],
//                ]
//            );

        $this->projectionProvider
            ->expects($this->exactly(2))
            ->method('updateProjection')
            ->willReturnMap(
                [
                    [
                        $this->streamName, [
                            'position' => '{"customer":5}',
                            'state' => '{"count":5}',
                            'locked_until' => 'time',
                        ],
                    ],
                    [
                        $this->streamName, [
                            'status' => ProjectionStatus::IDLE->value,
                        ],
                    ],
                ]
            )->willReturn(true);

        $store = $this->standaloneProjectionInstance($context);

        $this->assertTrue($store->stop());
        $this->assertTrue($context->runner->isStopped());
        $this->assertEquals(ProjectionStatus::IDLE, $context->status);
    }

    #[Test]
    public function it_fails_stop_projection(): void
    {
        $context = $this->newContext();
        $context->runner->stop(false);
        $context->status = ProjectionStatus::RUNNING;

        $context->state->put(['count' => 5]);

        $this->projectorLock->expects($this->once())
            ->method('refresh')
            ->willReturn('time_with_milliseconds');

        $this->position->expects($this->any())
            ->method('all')
            ->willReturn(['customer' => 5]);

        $this->jsonSerializer->expects($this->exactly(2))
            ->method('encode');
//            ->willReturnMap([
//                [['count' => 5], '{"count":5}'],
//                [['customer' => 5], '{"customer":5}'],
//            ]);

        $this->projectionProvider->expects($this->exactly(2))
            ->method('updateProjection')
            ->willReturnMap([
                [
                    $this->streamName, [
                        'locked_until' => 'time_with_milliseconds',
                        'position' => '{"customer":5}',
                        'state' => '{"count":5}',
                    ],
                ],
                [
                    $this->streamName, [
                        'status' => ProjectionStatus::IDLE->value,
                    ],
                ],
            ])->willReturn(true, false);

        $store = $this->standaloneProjectionInstance($context);
        $this->assertFalse($store->stop());

        $this->assertTrue($context->runner->isStopped());
        $this->assertEquals(ProjectionStatus::RUNNING, $context->status);
    }

    #[Test]
    public function it_start_again_projection(): void
    {
        $context = $this->newContext();
        $context->runner->stop(true);
        $context->status = ProjectionStatus::STOPPING;

        $this->projectorLock->expects($this->once())
            ->method('acquire')
            ->willReturn('some_time_in_ms');

        $this->projectionProvider->expects($this->once())
            ->method('updateProjection')
            ->with($this->streamName, [
                'status' => ProjectionStatus::RUNNING->value,
                'locked_until' => 'some_time_in_ms',
            ])
            ->willReturn(true);

        $store = $this->standaloneProjectionInstance($context);
        $this->assertTrue($store->startAgain());

        $this->assertFalse($context->runner->isStopped());
        $this->assertEquals(ProjectionStatus::RUNNING, $context->status);
    }

    #[Test]
    public function it_fails_start_again_projection(): void
    {
        $context = $this->newContext();
        $context->runner->stop(true);
        $context->status = ProjectionStatus::STOPPING;

        $this->projectorLock->expects($this->once())
            ->method('acquire')
            ->willReturn('some_time_in_ms');

        $this->projectionProvider->expects($this->once())
            ->method('updateProjection')
            ->with($this->streamName, [
                'status' => ProjectionStatus::RUNNING->value,
                'locked_until' => 'some_time_in_ms',
            ])
            ->willReturn(false);

        $store = $this->standaloneProjectionInstance($context);
        $this->assertFalse($store->startAgain());

        $this->assertFalse($context->runner->isStopped());
        $this->assertEquals(ProjectionStatus::STOPPING, $context->status);
    }

    #[Test]
    public function it_reset_projection(): void
    {
        $context = $this->newContext();

        $context->initialize(fn (): array => ['count' => 0]);
        $context->state->put(['count' => 20]);

        $context->status = ProjectionStatus::RESETTING;

        $this->position->expects($this->once())->method('reset');
        $this->position->expects($this->once())->method('all')->willReturn([]);

        $this->jsonSerializer
            ->expects($this->exactly(2))
            ->method('encode');
//            ->willReturnMap([
//                [
//                    [], '{}'
//                ],
//                [
//                    ['count' => 0], '{"count":0}'
//                ],
//            ]);

        $this->projectionProvider->expects($this->once())
            ->method('updateProjection')
            ->with($this->streamName, [
                'position' => '', // {}
                'state' => '', // '{"count":0}'
                'status' => ProjectionStatus::RESETTING->value,
            ])
            ->willReturn(true);

        $store = $this->standaloneProjectionInstance($context);

        $this->assertTrue($store->reset());
        $this->assertEquals(['count' => 0], $context->state->get());
    }

    #[Test]
    public function it_fails_reset_projection(): void
    {
        $context = $this->newContext();

        $context->initialize(fn (): array => ['count' => 0]);
        $context->state->put(['count' => '5']);
        $context->status = ProjectionStatus::RESETTING;

        $this->position->expects($this->once())->method('reset');
        $this->position->expects($this->once())->method('all')->willReturn(['customer' => 5]);

        $this->jsonSerializer
            ->method('encode');
//            ->willReturnMap([
//                [['customer' => 5], '{}'],
//                [['count' => 0], '{"count":0}'],
//            ]);

        $this->projectionProvider->expects($this->once())
            ->method('updateProjection')
            ->with($this->streamName, [
                'position' => '', //{}
                'state' => '', // '{"count":0}'
                'status' => ProjectionStatus::RESETTING->value,
            ])
            ->willReturn(false);

        $store = $this->standaloneProjectionInstance($context);
        $this->assertFalse($store->reset());

        $this->assertEquals(['count' => 0], $context->state->get());
    }

    #[DataProvider('provideBoolean')]
    #[Test]
    public function it_delete_projection(bool $withEmittedEvent): void
    {
        $context = $this->newContext();

        $this->assertFalse($context->runner->isStopped());

        $context->initialize(fn (): array => ['count' => 0]);
        $context->state->put(['foo' => 'bar']);

        $this->position->expects($this->once())->method('reset');

        $this->projectionProvider->expects($this->once())
            ->method('deleteProjection')
            ->with($this->streamName)
            ->willReturn(true);

        $store = $this->standaloneProjectionInstance($context);

        $this->assertTrue($store->delete($withEmittedEvent));
        $this->assertEquals(['count' => 0], $context->state->get());
        $this->assertTrue($context->runner->isStopped());
    }

    #[DataProvider('provideBoolean')]
    #[Test]
    public function it_fails_delete_projection(bool $withEmittedEvent): void
    {
        $context = $this->newContext();

        $context->state->put(['foo' => 'bar']);
        $context->initialize(fn (): array => ['count' => 0]);

        $this->position->expects($this->never())->method('reset');

        $this->projectionProvider->expects($this->once())
            ->method('deleteProjection')
            ->with($this->streamName)
            ->willReturn(false);

        $store = $this->standaloneProjectionInstance($context);

        $this->assertFalse($store->delete($withEmittedEvent));
        $this->assertEquals(['foo' => 'bar'], $context->state->get());
    }

    #[Test]
    public function it_load_projection_status_and_return_running_status_when_projection_model_not_found(): void
    {
        $context = $this->newContext();

        $this->projectionProvider->expects($this->once())
            ->method('retrieve')
            ->with($this->streamName)
            ->willReturn(null);

        $store = $this->standaloneProjectionInstance($context);

        $this->assertEquals(ProjectionStatus::RUNNING, $store->loadStatus());
    }

    #[DataProvider('provideProjectionStatus')]
    #[Test]
    public function it_load_projection_status_and_return_status_from_projection_model(ProjectionStatus $projectionStatus): void
    {
        $projectionModel = $this->createMock(ProjectionModel::class);
        $projectionModel->expects($this->once())
            ->method('status')
            ->willReturn($projectionStatus->value);

        $context = $this->newContext();

        $this->projectionProvider->expects($this->once())
            ->method('retrieve')
            ->with($this->streamName)
            ->willReturn($projectionModel);

        $store = $this->standaloneProjectionInstance($context);

        $this->assertEquals($projectionStatus, $store->loadStatus());
    }

    #[Test]
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
            $this->projectionProvider,
            $this->projectorLock,
            $this->jsonSerializer,
            $this->streamName
        );
    }

    public static function provideBoolean(): Generator
    {
        yield [true];
        yield [false];
    }

    public static function provideProjectionStatus(): Generator
    {
        yield [ProjectionStatus::RUNNING];
        yield [ProjectionStatus::IDLE];
        yield [ProjectionStatus::STOPPING];
        yield [ProjectionStatus::RESETTING];
        yield [ProjectionStatus::DELETING];
        yield [ProjectionStatus::DELETING_WITH_EMITTED_EVENTS];
    }
}
