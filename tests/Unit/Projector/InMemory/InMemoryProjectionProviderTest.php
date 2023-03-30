<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\InMemory;

use DateInterval;
use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Contracts\Projector\ProjectionModel;
use Chronhub\Storm\Projector\Provider\InMemoryProjection;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Projector\Provider\InMemoryProjectionProvider;

#[CoversClass(InMemoryProjectionProvider::class)]
final class InMemoryProjectionProviderTest extends UnitTestCase
{
    private PointInTime $clock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->clock = new PointInTime();
    }

    public function testProjectionInstance(): void
    {
        $provider = new InMemoryProjectionProvider($this->clock);

        $this->assertNull($provider->retrieve('account'));

        $this->assertTrue($provider->createProjection('account', 'running'));

        $projection = $provider->retrieve('account');

        $this->assertInstanceOf(InMemoryProjection::class, $projection);
    }

    public function testReturnFalseWhenProjectionAlreadyExists(): void
    {
        $provider = new InMemoryProjectionProvider($this->clock);

        $this->assertNull($provider->retrieve('account'));

        $this->assertTrue($provider->createProjection('account', 'running'));

        $projection = $provider->retrieve('account');

        $this->assertInstanceOf(ProjectionModel::class, $projection);

        $this->assertFalse($provider->createProjection('account', 'running'));
    }

    public function testUpdateProjection(): void
    {
        $provider = new InMemoryProjectionProvider($this->clock);

        $this->assertTrue($provider->createProjection('account', 'running'));

        $projection = $provider->retrieve('account');

        $this->assertEquals('account', $projection->name());
        $this->assertEquals('running', $projection->status());
        $this->assertEquals('{}', $projection->state());
        $this->assertEquals('{}', $projection->position());
        $this->assertNull($projection->lockedUntil());

        $this->assertFalse($provider->updateProjection('customer', []));

        $updated = $provider->updateProjection('account', [
            'state' => '{"count":0}',
            'position' => '{"customer":0}',
            'status' => 'idle',
            'locked_until' => 'datetime',
        ]);

        $this->assertTrue($updated);

        $projection = $provider->retrieve('account');

        $this->assertEquals('account', $projection->name());
        $this->assertEquals('idle', $projection->status());
        $this->assertEquals('{"count":0}', $projection->state());
        $this->assertEquals('{"customer":0}', $projection->position());
        $this->assertEquals('datetime', $projection->lockedUntil());
    }

    public function testExceptionRaisedWithUnknownField(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid projection field invalid_field for projection account');

        $provider = new InMemoryProjectionProvider($this->clock);

        $this->assertTrue($provider->createProjection('account', 'running'));

        $provider->updateProjection('account', ['invalid_field' => '{"count" => 10}']);
    }

    public function testDeleteProjection(): void
    {
        $provider = new InMemoryProjectionProvider($this->clock);

        $this->assertTrue($provider->createProjection('customer', 'running'));

        $this->assertInstanceOf(ProjectionModel::class, $provider->retrieve('customer'));

        $deleted = $provider->deleteProjection('customer');

        $this->assertTrue($deleted);

        $this->assertNull($provider->retrieve('customer'));
    }

    #[Test]
    public function testReturnFalseWhenDeletingProjectionNotFound(): void
    {
        $provider = new InMemoryProjectionProvider($this->clock);

        $this->assertNull($provider->retrieve('customer'));

        $this->assertFalse($provider->deleteProjection('customer'));
    }

    #[Test]
    public function testFilterProjectionNames(): void
    {
        $provider = new InMemoryProjectionProvider($this->clock);

        $this->assertTrue($provider->createProjection('customer', 'running'));
        $this->assertTrue($provider->createProjection('account', 'running'));

        $this->assertCount(1, $provider->filterByNames('customer'));
        $this->assertCount(1, $provider->filterByNames('account'));
        $this->assertCount(2, $provider->filterByNames('customer', 'account'));

        $found = $provider->filterByNames('customer', 'account', 'emails');

        $this->assertCount(2, $found);
        $this->assertEquals(['customer', 'account'], $found);
    }

    public function testLockIsAcquired(): void
    {
        $provider = new InMemoryProjectionProvider($this->clock);

        $this->assertTrue($provider->createProjection('customer', 'idle'));
        $this->assertNull($provider->retrieve('customer')->lockedUntil());

        $now = $this->clock->now();
        $lock = $now->add(new DateInterval('PT1H'));

        $acquired = $provider->acquireLock(
            'customer',
            'running',
            $lock->format($this->clock::DATE_TIME_FORMAT),
            $now->format($this->clock::DATE_TIME_FORMAT)
        );

        $this->assertTrue($acquired);

        $projection = $provider->retrieve('customer');

        $this->assertEquals($lock->format($this->clock::DATE_TIME_FORMAT), $projection->lockedUntil());
        $this->assertEquals('running', $projection->status());
    }

    public function testReturnFalseWhenAcquireLockFromProjectionNotFound(): void
    {
        $provider = new InMemoryProjectionProvider($this->clock);

        $this->assertNull($provider->retrieve('customer'));

        $acquired = $provider->acquireLock('customer', 'running', 'lock', 'now');

        $this->assertFalse($acquired);
    }

    public function testAcquireLockWhenCurrentTimeIsGreaterThanProjectionLock(): void
    {
        $now = $this->clock->now();
        $lock = $now->sub(new DateInterval('PT1H'));

        $nowString = $now->format($this->clock::DATE_TIME_FORMAT);
        $lockString = $lock->format($this->clock::DATE_TIME_FORMAT);

        $provider = new InMemoryProjectionProvider($this->clock);

        $this->assertTrue($provider->createProjection('customer', 'idle'));
        $this->assertNull($provider->retrieve('customer')->lockedUntil());

        $provider->acquireLock('customer', 'running', $lockString, $nowString);
        $this->assertEquals($lockString, $provider->retrieve('customer')->lockedUntil());

        // use now io lock
        $provider->acquireLock('customer', 'running', $nowString, $nowString);
        $this->assertEquals($nowString, $provider->retrieve('customer')->lockedUntil());
    }

    public function testAcquireLockNotUpdatedWhenCurrentTimeIsLessThanProjectionLock(): void
    {
        $provider = new InMemoryProjectionProvider($this->clock);

        $this->assertTrue($provider->createProjection('customer', 'idle'));
        $this->assertNull($provider->retrieve('customer')->lockedUntil());

        $now = $this->clock->now();
        $lock = $now->add(new DateInterval('PT1H'));

        $nowString = $now->format($this->clock::DATE_TIME_FORMAT);
        $lockString = $lock->format($this->clock::DATE_TIME_FORMAT);

        $acquired = $provider->acquireLock('customer', 'running', $lockString, $nowString);

        $this->assertTrue($acquired);
        $this->assertEquals($lockString, $provider->retrieve('customer')->lockedUntil());

        $notAcquired = $provider->acquireLock('customer', 'running', $lockString, $nowString);
        $this->assertFalse($notAcquired);

        $this->assertEquals($lockString, $provider->retrieve('customer')->lockedUntil());
    }
}
