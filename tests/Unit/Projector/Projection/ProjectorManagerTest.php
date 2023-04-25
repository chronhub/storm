<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Projector\Projection;

use Chronhub\Storm\Contracts\Projector\EmitterProjector;
use Chronhub\Storm\Contracts\Projector\ProjectorManagerInterface;
use Chronhub\Storm\Contracts\Projector\QueryProjector;
use Chronhub\Storm\Contracts\Projector\ReadModelProjector;
use Chronhub\Storm\Projector\AbstractSubscriptionFactory;
use Chronhub\Storm\Projector\Options\DefaultProjectionOption;
use Chronhub\Storm\Projector\Options\InMemoryProjectionOption;
use Chronhub\Storm\Projector\ProjectEmitter;
use Chronhub\Storm\Projector\ProjectorManager;
use Chronhub\Storm\Projector\ProjectQuery;
use Chronhub\Storm\Projector\ProjectReadModel;
use Chronhub\Storm\Projector\ReadModel\InMemoryReadModel;
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

        $projector = $manager->query();

        $this->assertInstanceOf(QueryProjector::class, $projector);
        $this->assertEquals(ProjectQuery::class, $projector::class);

        $subscription = ReflectionProperty::getProperty($projector, 'subscription');
        $this->assertEquals(InMemoryProjectionOption::class, $subscription->option()::class);
    }

    public function testEmitterProjector(): void
    {
        $manager = new ProjectorManager($this->createSubscriptionFactory());

        $projector = $manager->emitter($this->streamName->name);

        $this->assertInstanceOf(EmitterProjector::class, $projector);
        $this->assertEquals(ProjectEmitter::class, $projector::class);

        $subscription = ReflectionProperty::getProperty($projector, 'subscription');
        $this->assertEquals(InMemoryProjectionOption::class, $subscription->option()::class);
    }

    public function testReadModelProjector(): void
    {
        $manager = new ProjectorManager($this->createSubscriptionFactory());

        $projector = $manager->readModel($this->streamName->name, new InMemoryReadModel());

        $this->assertInstanceOf(ReadModelProjector::class, $projector);
        $this->assertEquals(ProjectReadModel::class, $projector::class);

        $subscription = ReflectionProperty::getProperty($projector, 'subscription');
        $this->assertEquals(InMemoryProjectionOption::class, $subscription->option()::class);
    }

    public function testProjectorWithOptions(): void
    {
        $defaultOption = new DefaultProjectionOption();
        $this->assertFalse($defaultOption->getSignal());

        $manager = new ProjectorManager($this->createSubscriptionFactory(['signal' => true]));
        $projector = $manager->query();

        $subscription = ReflectionProperty::getProperty($projector, 'subscription');

        $option = $subscription->option();
        $this->assertEquals(DefaultProjectionOption::class, $option::class);
        $this->assertTrue($option->getSignal());
    }

    public function testProjectorWithOptionsOverridden(): void
    {
        $defaultOption = new DefaultProjectionOption();
        $this->assertFalse($defaultOption->getSignal());

        $manager = new ProjectorManager($this->createSubscriptionFactory(['signal' => true]));
        $projector = $manager->query(['signal' => false]);

        $subscription = ReflectionProperty::getProperty($projector, 'subscription');

        $option = $subscription->option();
        $this->assertEquals(DefaultProjectionOption::class, $option::class);
        $this->assertFalse($option->getSignal());
    }
}