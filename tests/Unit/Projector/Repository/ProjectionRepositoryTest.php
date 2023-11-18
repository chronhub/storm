<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Repository;

use Chronhub\Storm\Contracts\Projector\ProjectionModel;
use Chronhub\Storm\Contracts\Projector\ProjectionProvider;
use Chronhub\Storm\Contracts\Serializer\JsonSerializer;
use Chronhub\Storm\Projector\Exceptions\ProjectionNotFound;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Repository\LockManager;
use Chronhub\Storm\Projector\Repository\ProjectionRepository;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

use function json_decode;
use function json_encode;

#[CoversClass(ProjectionRepository::class)]
final class ProjectionRepositoryTest extends UnitTestCase
{
    private ProjectionProvider|MockObject $provider;

    private JsonSerializer|MockObject $json;

    private LockManager|MockObject $lock;

    protected function setUp(): void
    {
        $this->provider = $this->createMock(ProjectionProvider::class);
        $this->json = $this->createMock(JsonSerializer::class);
        $this->lock = $this->createMock(LockManager::class);
    }

    #[DataProvider('provideBoolean')]
    public function testCreateSuccess(bool $success): void
    {
        $this->provider
            ->expects($this->once())
            ->method('createProjection')
            ->with('stream_name', ProjectionStatus::IDLE->value)
            ->willReturn($success);

        $this->assertSame($success, $this->newRepository()->create(ProjectionStatus::IDLE));
    }

    #[DataProvider('provideBoolean')]
    public function testStopProjectionWithSuccessPersist(bool $successOnUpdateStatus): void
    {
        $streamPositions = ['stream_name' => 1];
        $state = ['count' => 1];

        $this->json
            ->expects($this->exactly(2))
            ->method('encode')
            ->will($this->returnCallback(fn ($value): string => json_encode($value)));

        $this->lock
            ->expects($this->once())
            ->method('refresh')
            ->willReturn('refreshed_datetime');

        $this->provider
            ->expects($this->exactly(2))
            ->method('updateProjection')
            ->willReturnMap([
                [
                    'stream_name',
                    [
                        'position' => '{"stream_name":1}',
                        'state' => '{"count":1}',
                        'locked_until' => 'refreshed_datetime',
                    ],
                    true,
                ],
                [
                    'stream_name',
                    [
                        'status' => ProjectionStatus::IDLE->value,
                    ],
                    $successOnUpdateStatus,
                ],
            ]);

        $this->assertSame($successOnUpdateStatus, $this->newRepository()->stop($streamPositions, $state));
    }

    public function testStopProjectionWithFailedPersist(): void
    {
        $streamPositions = ['stream_name' => 1];
        $state = ['count' => 1];

        $this->json
            ->expects($this->exactly(2))
            ->method('encode')
            ->will($this->returnCallback(function ($value) {
                return json_encode($value);
            }));

        $this->lock
            ->expects($this->once())
            ->method('refresh')
            ->willReturn('refreshed_datetime');

        $this->provider
            ->expects($this->once())
            ->method('updateProjection')
            ->with(
                'stream_name',
                [
                    'position' => '{"stream_name":1}',
                    'state' => '{"count":1}',
                    'locked_until' => 'refreshed_datetime',
                ],
            )
            ->willReturn(false);

        $this->assertFalse($this->newRepository()->stop($streamPositions, $state));
    }

    #[DataProvider('provideBoolean')]
    public function testStartAgain(bool $success): void
    {
        $this->lock
            ->expects($this->once())
            ->method('acquire')
            ->willReturn('acquired_datetime');

        $this->provider
            ->expects($this->once())
            ->method('updateProjection')
            ->with(
                'stream_name',
                [
                    'status' => ProjectionStatus::RUNNING->value,
                    'locked_until' => 'acquired_datetime',
                ],
            )
            ->willReturn($success);

        $this->assertSame($success, $this->newRepository()->startAgain());
    }

    #[DataProvider('provideBoolean')]
    public function testReset(bool $success): void
    {
        $streamPositions = ['stream_name' => 1];
        $state = ['count' => 1];

        $this->json
            ->expects($this->exactly(2))
            ->method('encode')
            ->will($this->returnCallback(fn ($value): string => json_encode($value)));

        $this->provider
            ->expects($this->once())
            ->method('updateProjection')
            ->with(
                'stream_name',
                [
                    'position' => '{"stream_name":1}',
                    'state' => '{"count":1}',
                    'status' => ProjectionStatus::RESETTING->value,
                ],
            )
            ->willReturn($success);

        $result = $this->newRepository()->reset($streamPositions, $state, ProjectionStatus::RESETTING);

        $this->assertSame($success, $result);
    }

    #[DataProvider('provideBoolean')]
    public function testDelete(bool $success): void
    {
        $this->provider
            ->expects($this->once())
            ->method('deleteProjection')
            ->with('stream_name')
            ->willReturn($success);

        $result = $this->newRepository()->delete();

        $this->assertSame($success, $result);
    }

    public function testLoadState(): void
    {
        $model = $this->createMock(ProjectionModel::class);
        $model->expects($this->once())->method('positions')->willReturn('{"stream_name":1}');
        $model->expects($this->once())->method('state')->willReturn('{"count":1}');

        $this->provider
            ->expects($this->once())
            ->method('retrieve')
            ->with('stream_name')
            ->willReturn($model);

        $this->json
            ->expects($this->exactly(2))
            ->method('decode')
            ->will($this->returnCallback(fn ($value): array => json_decode($value, true)));

        $result = $this->newRepository()->loadDetail();

        $this->assertSame([['stream_name' => 1], ['count' => 1]], $result);
    }

    public function testExceptionRaisedOnLoadState(): void
    {
        $this->expectException(ProjectionNotFound::class);
        $this->expectExceptionMessage('Projection stream_name not found');

        $this->provider
            ->expects($this->once())
            ->method('retrieve')
            ->with('stream_name')
            ->willReturn(null);

        $this->json->expects($this->never())->method('decode');

        $this->newRepository()->loadDetail();
    }

    #[DataProvider('provideProjectionStatus')]
    public function testLoadStatus(ProjectionStatus $status): void
    {
        $model = $this->createMock(ProjectionModel::class);
        $model->expects($this->once())->method('status')->willReturn($status->value);

        $this->provider
            ->expects($this->once())
            ->method('retrieve')
            ->with('stream_name')
            ->willReturn($model);

        $statusLoaded = $this->newRepository()->loadStatus();

        $this->assertSame($statusLoaded, $status);
    }

    public function testReturnRunningStatusWhenProjectionNotFound(): void
    {
        $this->provider
            ->expects($this->once())
            ->method('retrieve')
            ->with('stream_name')
            ->willReturn(null);

        $this->assertSame(ProjectionStatus::RUNNING, $this->newRepository()->loadStatus());
    }

    #[DataProvider('provideBoolean')]
    public function testAcquireLock(bool $success): void
    {
        $this->lock
            ->expects($this->once())
            ->method('acquire')
            ->willReturn('acquired_datetime');

        $this->lock
            ->expects($this->once())
            ->method('current')
            ->willReturn('current_datetime');

        $this->provider
            ->expects($this->once())
            ->method('acquireLock')
            ->with(
                'stream_name',
                ProjectionStatus::RUNNING->value,
                'acquired_datetime',
                'current_datetime'
            )
            ->willReturn($success);

        $this->assertSame($success, $this->newRepository()->acquireLock());
    }

    #[DataProvider('provideBoolean')]
    public function testUpdateLockWithStreamPositions(bool $successOnUpdate): void
    {
        $this->json
            ->expects($this->once())
            ->method('encode')
            ->with(['stream_name' => 1])
            ->willReturn('{"stream_name":1}');

        $this->lock
            ->expects($this->once())
            ->method('tryUpdate')
            ->willReturn(true);

        $this->lock
            ->expects($this->once())
            ->method('increment')
            ->willReturn('increment_datetime');

        $this->provider
            ->expects($this->once())
            ->method('updateProjection')
            ->with(
                'stream_name',
                [
                    'position' => '{"stream_name":1}',
                    'locked_until' => 'increment_datetime',
                ],
            )->willReturn($successOnUpdate);

        $this->assertSame($successOnUpdate, $this->newRepository()->attemptUpdateStreamPositions(['stream_name' => 1]));
    }

    public function testUpdateLockAlwaysReturnTrueWhenUpdateLockFailed(): void
    {
        $this->json->expects($this->never())->method('encode');

        $this->lock
            ->expects($this->once())
            ->method('tryUpdate')
            ->willReturn(false);

        $this->lock->expects($this->never())->method('increment');
        $this->provider->expects($this->never())->method('updateProjection');

        $this->assertTrue($this->newRepository()->attemptUpdateStreamPositions(['stream_name' => 1]));
    }

    #[DataProvider('provideBoolean')]
    public function testReleaseLock(bool $success): void
    {
        $this->provider
            ->expects($this->once())
            ->method('updateProjection')
            ->with(
                'stream_name',
                [
                    'status' => ProjectionStatus::IDLE->value,
                    'locked_until' => null,
                ],
            )
            ->willReturn($success);

        $this->assertSame($success, $this->newRepository()->releaseLock());
    }

    #[DataProvider('provideBoolean')]
    public function testProjectionExists(bool $success): void
    {
        $this->provider
            ->expects($this->once())
            ->method('exists')
            ->with('stream_name')
            ->willReturn($success);

        $this->assertSame($success, $this->newRepository()->exists());
    }

    public function testGetProjectionName(): void
    {
        $this->assertSame('stream_name', $this->newRepository()->projectionName());
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
        yield [ProjectionStatus::RESETTING];
        yield [ProjectionStatus::DELETING];
        yield [ProjectionStatus::DELETING_WITH_EMITTED_EVENTS];
        yield [ProjectionStatus::STOPPING];
    }

    private function newRepository(): ProjectionRepository
    {
        return new ProjectionRepository(
            $this->provider,
            $this->lock,
            $this->json,
            'stream_name'
        );
    }
}
