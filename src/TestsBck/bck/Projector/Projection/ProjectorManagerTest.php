<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Projection;

use Chronhub\Storm\Contracts\Projector\EmitterProjector;
use Chronhub\Storm\Contracts\Projector\ProjectorManagerInterface;
use Chronhub\Storm\Contracts\Projector\QueryProjector;
use Chronhub\Storm\Contracts\Projector\ReadModelProjector;
use Chronhub\Storm\Projector\Options\DefaultOption;
use Chronhub\Storm\Projector\Options\InMemoryOption;
use Chronhub\Storm\Projector\ProjectEmitter;
use Chronhub\Storm\Projector\ProjectorManager;
use Chronhub\Storm\Projector\ProjectQuery;
use Chronhub\Storm\Projector\ProjectReadModel;
use Chronhub\Storm\Projector\ReadModel\InMemoryReadModel;
use Chronhub\Storm\Projector\Subscription\AbstractSubscriptionFactory;
use Chronhub\Storm\Stream\StreamName;
use Chronhub\Storm\Tests\Util\ReflectionProperty;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractSubscriptionFactory::class)]
#[CoversClass(ProjectorManager::class)]
final class ProjectorManagerTest extends InMemoryProjectorManagerTestCase
{
    private StreamName $streamName;

    protected function setUp(): void
    {
        parent::setUp();

        $this->streamName = new StreamName('balance');
    }

    public function testInstance(): void
    {
        $manager = new ProjectorManager($this->createSubscriptionFactory());

        $this->assertInstanceOf(ProjectorManagerInterface::class, $manager);
    }

    public function testQueryProjector(): void
    {
        $manager = new ProjectorManager($this->createSubscriptionFactory());

        $projector = $manager->newQuery();

        $this->assertInstanceOf(QueryProjector::class, $projector);
        $this->assertEquals(ProjectQuery::class, $projector::class);

        $subscription = ReflectionProperty::getProperty($projector, 'subscription');
        $this->assertEquals(InMemoryOption::class, $subscription->option()::class);
    }

    public function testEmitterProjector(): void
    {
        $manager = new ProjectorManager($this->createSubscriptionFactory());

        $projector = $manager->newEmitter($this->streamName->name);

        $this->assertInstanceOf(EmitterProjector::class, $projector);
        $this->assertEquals(ProjectEmitter::class, $projector::class);

        $subscription = ReflectionProperty::getProperty($projector, 'subscription');
        $this->assertEquals(InMemoryOption::class, $subscription->option()::class);
    }

    public function testReadModelProjector(): void
    {
        $manager = new ProjectorManager($this->createSubscriptionFactory());

        $projector = $manager->newReadModel($this->streamName->name, new InMemoryReadModel());

        $this->assertInstanceOf(ReadModelProjector::class, $projector);
        $this->assertEquals(ProjectReadModel::class, $projector::class);

        $subscription = ReflectionProperty::getProperty($projector, 'subscription');
        $this->assertEquals(InMemoryOption::class, $subscription->option()::class);
    }

    public function testProjectorWithOptions(): void
    {
        $defaultOption = new DefaultOption();
        $this->assertFalse($defaultOption->getSignal());

        $manager = new ProjectorManager($this->createSubscriptionFactory(['signal' => true]));
        $projector = $manager->newQuery();

        $subscription = ReflectionProperty::getProperty($projector, 'subscription');

        $option = $subscription->option();
        $this->assertEquals(DefaultOption::class, $option::class);
        $this->assertTrue($option->getSignal());
    }

    public function testProjectorWithOptionsOverridden(): void
    {
        $defaultOption = new DefaultOption();
        $this->assertFalse($defaultOption->getSignal());

        $manager = new ProjectorManager($this->createSubscriptionFactory(['signal' => true]));
        $projector = $manager->newQuery(['signal' => false]);

        $subscription = ReflectionProperty::getProperty($projector, 'subscription');

        $option = $subscription->option();
        $this->assertEquals(DefaultOption::class, $option::class);
        $this->assertFalse($option->getSignal());
    }
}
