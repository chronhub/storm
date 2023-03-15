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
    #[Test]
    public function it_create_projection(): void
    {
        $provider = new InMemoryProjectionProvider();

        $this->assertNull($provider->retrieve('account'));

        $this->assertTrue($provider->createProjection('account', 'running'));

        $projection = $provider->retrieve('account');

        $this->assertInstanceOf(InMemoryProjection::class, $projection);
    }

    #[Test]
    public function it_return_false_when_creating_projection_if_already_exists(): void
    {
        $provider = new InMemoryProjectionProvider();

        $this->assertNull($provider->retrieve('account'));

        $this->assertTrue($provider->createProjection('account', 'running'));

        $projection = $provider->retrieve('account');

        $this->assertInstanceOf(ProjectionModel::class, $projection);

        $this->assertFalse($provider->createProjection('account', 'running'));
    }

    #[Test]
    public function it_update_projection(): void
    {
        $provider = new InMemoryProjectionProvider();

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

    #[Test]
    public function it_raise_exception_when_updating_projection_with_unknown_field(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid projection field invalid_field for projection account');

        $provider = new InMemoryProjectionProvider();

        $this->assertTrue($provider->createProjection('account', 'running'));

        $provider->updateProjection('account', ['invalid_field' => '{"count" => 10}']);
    }

    #[Test]
    public function it_delete_projection(): void
    {
        $provider = new InMemoryProjectionProvider();

        $this->assertTrue($provider->createProjection('customer', 'running'));

        $this->assertInstanceOf(ProjectionModel::class, $provider->retrieve('customer'));

        $deleted = $provider->deleteProjection('customer');

        $this->assertTrue($deleted);

        $this->assertNull($provider->retrieve('customer'));
    }

    #[Test]
    public function it_return_false_deleting_not_found_projection(): void
    {
        $provider = new InMemoryProjectionProvider();

        $this->assertNull($provider->retrieve('customer'));

        $this->assertFalse($provider->deleteProjection('customer'));
    }

    #[Test]
    public function it_find_projection_by_names_and_order_ascendant(): void
    {
        $provider = new InMemoryProjectionProvider();

        $this->assertTrue($provider->createProjection('customer', 'running'));
        $this->assertTrue($provider->createProjection('account', 'running'));
        $this->assertCount(1, $provider->filterByNames('customer'));
        $this->assertCount(1, $provider->filterByNames('account'));
        $this->assertCount(2, $provider->filterByNames('customer', 'account'));

        $found = $provider->filterByNames('customer', 'account', 'emails');

        $this->assertCount(2, $found);
        $this->assertEquals(['customer', 'account'], $found);
    }

    #[Test]
    public function it_acquire_lock_with_null_projection_lock(): void
    {
        $provider = new InMemoryProjectionProvider();

        $this->assertTrue($provider->createProjection('customer', 'idle'));
        $this->assertNull($provider->retrieve('customer')->lockedUntil());

        $clock = new PointInTime();
        $now = $clock->now();
        $lock = $now->add(new DateInterval('PT1H'));

        $acquired = $provider->acquireLock('customer', 'running', $lock->format($clock::DATE_TIME_FORMAT), $now->format($clock::DATE_TIME_FORMAT));
        $this->assertTrue($acquired);

        $projection = $provider->retrieve('customer');

        $this->assertEquals($lock->format($clock::DATE_TIME_FORMAT), $projection->lockedUntil());
        $this->assertEquals('running', $projection->status());
    }

    #[Test]
    public function it_return_false_acquiring_lock_with_not_found_projection(): void
    {
        $provider = new InMemoryProjectionProvider();

        $this->assertNull($provider->retrieve('customer'));

        $acquired = $provider->acquireLock('customer', 'running', 'lock', 'now');

        $this->assertFalse($acquired);
    }

    #[Test]
    public function it_acquire_lock_when_now_is_greater_than_lock_projection(): void
    {
        $clock = new PointInTime();
        $now = $clock->now();
        $lock = $now->sub(new DateInterval('PT1H'));

        $nowString = $now->format($clock::DATE_TIME_FORMAT);
        $lockString = $lock->format($clock::DATE_TIME_FORMAT);

        $provider = new InMemoryProjectionProvider();

        $this->assertTrue($provider->createProjection('customer', 'idle'));
        $this->assertNull($provider->retrieve('customer')->lockedUntil());

        $provider->acquireLock('customer', 'running', $lockString, $nowString);
        $this->assertEquals($lockString, $provider->retrieve('customer')->lockedUntil());

        // use now io lock
        $provider->acquireLock('customer', 'running', $nowString, $nowString);
        $this->assertEquals($nowString, $provider->retrieve('customer')->lockedUntil());
    }

    #[Test]
    public function it_does_not_acquire_lock_when_now_is_less_than_lock_projection(): void
    {
        $provider = new InMemoryProjectionProvider();

        $this->assertTrue($provider->createProjection('customer', 'idle'));
        $this->assertNull($provider->retrieve('customer')->lockedUntil());

        $clock = new PointInTime();
        $now = $clock->now();
        $lock = $now->add(new DateInterval('PT1H'));

        $nowString = $now->format($clock::DATE_TIME_FORMAT);
        $lockString = $lock->format($clock::DATE_TIME_FORMAT);

        $acquired = $provider->acquireLock('customer', 'running', $lockString, $nowString);

        $this->assertTrue($acquired);
        $this->assertEquals($lockString, $provider->retrieve('customer')->lockedUntil());

        $notAcquired = $provider->acquireLock('customer', 'running', $lockString, $nowString);
        $this->assertFalse($notAcquired);

        $this->assertEquals($lockString, $provider->retrieve('customer')->lockedUntil());
    }
}
