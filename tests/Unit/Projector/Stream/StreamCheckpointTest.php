<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Stream;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Projector\Checkpoint\CheckpointCollection;
use Chronhub\Storm\Projector\Checkpoint\CheckpointManager;
use Chronhub\Storm\Projector\Checkpoint\GapDetector;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;

beforeEach(function () {
    $this->clock = $this->createMock(SystemClock::class);
    $this->clock->expects($this->any())->method('toString')->willReturn('now');
    $this->collection = new CheckpointCollection($this->clock);
    $this->gapDetector = new GapDetector([1, 2, 3]);
});

describe('start from scratch', function () {
    it('add checkpoint', function () {
        $manager = new CheckpointManager($this->collection, $this->gapDetector);
        $manager->refreshStreams(['customer']);

        $positions = [1, 2, 3];

        foreach ($positions as $position) {
            $manager->insert('customer', $position);
        }

        expect($manager->hasGap())->toBeFalse()
            ->and($manager->checkpoints())->toHaveCount(1)
            ->and($manager->checkpoints())->toHaveKeys(['customer']);

        $customerCheckpoint = $manager->checkpoints()['customer'];

        expect($customerCheckpoint->streamName)->toEqual('customer')
            ->and($customerCheckpoint->position)->toEqual(3)
            ->and($customerCheckpoint->gaps)->toEqual([])
            ->and($customerCheckpoint->createdAt)->toEqual('now');
    });

    it('raise exception when add checkpoint from a not watched event stream', function () {
        $manager = new CheckpointManager($this->collection, $this->gapDetector);
        $manager->refreshStreams(['customer']);

        $positions = [1, 2, 3];

        foreach ($positions as $position) {
            $manager->insert('customer', $position);
            $manager->insert('order', $position);
        }
    })->throws(InvalidArgumentException::class, 'Event stream order is not watched');
});

describe('sync checkpoints', function () {
    it('add checkpoint', function () {
        $manager = new CheckpointManager($this->collection, $this->gapDetector);
        $manager->refreshStreams(['customer']);

        $manager->update([
            [
                'stream_name' => 'customer',
                'position' => 10,
                'gaps' => [],
                'created_at' => 'now',
            ],
        ]);

        $positions = [11, 12, 13];

        foreach ($positions as $position) {
            $manager->insert('customer', $position);
        }

        expect($manager->hasGap())->toBeFalse()
            ->and($manager->checkpoints())->toHaveCount(1)
            ->and($manager->checkpoints())->toHaveKeys(['customer']);

        $customerCheckpoint = $manager->checkpoints()['customer'];

        expect($customerCheckpoint->streamName)->toEqual('customer')
            ->and($customerCheckpoint->position)->toEqual(13)
            ->and($customerCheckpoint->gaps)->toEqual([])
            ->and($customerCheckpoint->createdAt)->toEqual('now');
    });

    it('does not add checkpoint when gap', function () {
        $manager = new CheckpointManager($this->collection, $this->gapDetector);
        $manager->refreshStreams(['customer']);

        $manager->update([
            [
                'stream_name' => 'customer',
                'position' => 10,
                'gaps' => [],
                'created_at' => 'now',
            ],
        ]);

        $positions = [11, 12, 14];

        foreach ($positions as $position) {
            $manager->insert('customer', $position);
        }

        expect($manager->hasGap())->toBeTrue()
            ->and($manager->checkpoints())->toHaveCount(1);

        $customerCheckpoint = $manager->checkpoints()['customer'];

        expect($customerCheckpoint->streamName)->toEqual('customer')
            ->and($customerCheckpoint->position)->toEqual(12)
            ->and($customerCheckpoint->gaps)->toEqual([])
            ->and($customerCheckpoint->createdAt)->toEqual('now');
    });

    it('add checkpoint with gap when no more retry', function () {
        $manager = new CheckpointManager($this->collection, $this->gapDetector);
        $manager->refreshStreams(['customer']);

        $manager->update([
            [
                'stream_name' => 'customer',
                'position' => 10,
                'gaps' => [],
                'created_at' => 'now',
            ],
        ]);

        $positions = [11, 12, 14];

        foreach ($positions as $position) {
            $manager->insert('customer', $position);
        }

        expect($manager->hasGap())->toBeTrue()
            ->and($manager->checkpoints()['customer']->position)->toEqual(12);

        // increase retry for customer
        $manager->insert('customer', 14);
        $manager->sleepWhenGap();
        expect($manager->hasGap())->toBeTrue();

        $manager->insert('customer', 14);
        $manager->sleepWhenGap();
        expect($manager->hasGap())->toBeTrue()
            ->and($manager->checkpoints()['customer']->position)->toEqual(12);

        $manager->insert('customer', 14);
        $manager->sleepWhenGap();
        expect($manager->hasGap())->toBeTrue()
            ->and($manager->checkpoints()['customer']->position)->toEqual(12);

        // gap recorded
        $manager->insert('customer', 14);

        expect($manager->hasGap())->toBeFalse()
            ->and($manager->checkpoints()['customer']->position)->toEqual(14)
            ->and($manager->checkpoints()['customer']->gaps)->toBe([13]);
    });

    it('add checkpoint with many gaps when no more retry', function () {
        $manager = new CheckpointManager($this->collection, $this->gapDetector);
        $manager->refreshStreams(['customer']);

        $manager->update([
            [
                'stream_name' => 'customer',
                'position' => 10,
                'gaps' => [],
                'created_at' => 'now',
            ],
        ]);

        $positions = [11, 12, 18];

        foreach ($positions as $position) {
            $manager->insert('customer', $position);
        }

        expect($manager->hasGap())->toBeTrue()
            ->and($manager->checkpoints()['customer']->position)->toEqual(12);

        // increase retry for customer
        $manager->insert('customer', 18);
        $manager->sleepWhenGap();
        expect($manager->hasGap())->toBeTrue();

        $manager->insert('customer', 18);
        $manager->sleepWhenGap();
        expect($manager->hasGap())->toBeTrue()
            ->and($manager->checkpoints()['customer']->position)->toEqual(12);

        $manager->insert('customer', 18);
        $manager->sleepWhenGap();
        expect($manager->hasGap())->toBeTrue()
            ->and($manager->checkpoints()['customer']->position)->toEqual(12);

        // gap recorded
        $manager->insert('customer', 18);

        expect($manager->hasGap())->toBeFalse()
            ->and($manager->checkpoints()['customer']->position)->toEqual(18)
            ->and($manager->checkpoints()['customer']->gaps)->toBe([13, 14, 15, 16, 17]);
    });

    it('raise exception when current event position is less than the last registered checkpoint position', function (int $outdatedPosition) {
        $manager = new CheckpointManager($this->collection, $this->gapDetector);
        $manager->refreshStreams(['customer']);

        $manager->update([
            [
                'stream_name' => 'customer',
                'position' => 10,
                'gaps' => [],
                'created_at' => 'now',
            ],
        ]);

        $manager->insert('customer', $outdatedPosition);
    })
        ->with([8, 9])
        ->throws(InvalidArgumentException::class, 'Position given for stream customer is outdated');
});
