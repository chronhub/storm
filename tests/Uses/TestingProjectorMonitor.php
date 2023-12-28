<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Uses;

use Chronhub\Storm\Contracts\Projector\ProjectionModel;
use Chronhub\Storm\Contracts\Projector\ProjectionProvider;
use Chronhub\Storm\Contracts\Serializer\JsonSerializer;
use Chronhub\Storm\Projector\ProjectorSupervisor;
use PHPUnit\Framework\MockObject\MockObject;
use tests\TestCase;

trait TestingProjectorMonitor
{
    protected ProjectionProvider&MockObject $projections;

    protected JsonSerializer&MockObject $serializer;

    protected ProjectionModel&MockObject $model;

    protected ProjectorSupervisor $projectorMonitor;

    protected string $projectionName = 'foo';

    protected function setupProjectorMonitor(): void
    {
        /** @var TestCase $this */
        $this->projections = $this->createMock(ProjectionProvider::class);
        $this->serializer = $this->createMock(JsonSerializer::class);
        $this->model = $this->createMock(ProjectionModel::class);

        $this->projectorMonitor = new ProjectorSupervisor($this->projections, $this->serializer);
    }

    protected function callUpdate(string $status): void
    {
        switch ($status) {
            case 'stopping':
                $this->projectorMonitor->markAsStop($this->projectionName);

                break;
            case 'resetting':
                $this->projectorMonitor->markAsReset($this->projectionName);

                break;
            case 'deleting':
                $this->projectorMonitor->markAsDelete($this->projectionName, false);

                break;
            case 'deleting_with_emitted_events':
                $this->projectorMonitor->markAsDelete($this->projectionName, true);

                break;
        }
    }

    protected function callGetter(string $getter): void
    {
        /** @var TestCase $this */
        switch ($getter) {
            case 'status':
                $this->model->expects($this->once())->method('status')->willReturn('running');

                expect($this->projectorMonitor->statusOf($this->projectionName))->toBe('running');

                break;
            case 'positions':
                $this->model->expects($this->once())->method('checkpoint')->willReturn('{"foo": 1}');
                $this->serializer->expects($this->once())->method('decode')->with('{"foo": 1}')->willReturn(['foo' => 1]);

                expect($this->projectorMonitor->checkpointOf($this->projectionName))->toBe(['foo' => 1]);

                break;
            case 'state':
                $this->model->expects($this->once())->method('state')->willReturn('{"foo": 1}');
                $this->serializer->expects($this->once())->method('decode')->with('{"foo": 1}')->willReturn(['foo' => 1]);

                expect($this->projectorMonitor->stateOf($this->projectionName))->toBe(['foo' => 1]);

                break;
        }
    }

    protected function failGetter(string $getter): void
    {

        /** @var TestCase $this */
        switch ($getter) {
            case 'status':
                $this->model->expects($this->never())->method('status');
                $this->projectorMonitor->statusOf($this->projectionName);

                break;
            case 'positions':
                $this->model->expects($this->never())->method('checkpoint');
                $this->projectorMonitor->checkpointOf($this->projectionName);

                break;
            case 'state':
                $this->model->expects($this->never())->method('state');
                $this->projectorMonitor->stateOf($this->projectionName);

                break;
        }
    }
}
