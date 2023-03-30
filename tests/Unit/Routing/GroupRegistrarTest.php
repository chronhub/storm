<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Routing;

use Chronhub\Storm\Routing\EventGroup;
use Chronhub\Storm\Routing\QueryGroup;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Reporter\DomainType;
use Chronhub\Storm\Routing\CommandGroup;
use Chronhub\Storm\Routing\GroupRegistrar;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Message\AliasFromClassName;
use Chronhub\Storm\Routing\Exceptions\RoutingViolation;

#[CoversClass(GroupRegistrar::class)]
final class GroupRegistrarTest extends UnitTestCase
{
    private GroupRegistrar $registrar;

    protected function setUp(): void
    {
        $this->registrar = new GroupRegistrar(new AliasFromClassName());
    }

    public function testGroupInstance(): void
    {
        $this->assertTrue($this->registrar->all()->isEmpty());

        $this->assertNotSame($this->registrar->all(), $this->registrar->all());
    }

    public function testGroupInstanceWithDomainType(): void
    {
        $commandGroup = $this->registrar->make(DomainType::COMMAND, 'default');
        $this->assertInstanceOf(CommandGroup::class, $commandGroup);

        $eventGroup = $this->registrar->make(DomainType::EVENT, 'default');
        $this->assertInstanceOf(EventGroup::class, $eventGroup);

        $queryGroup = $this->registrar->make(DomainType::QUERY, 'default');
        $this->assertInstanceOf(QueryGroup::class, $queryGroup);

        $this->assertNotSame($this->registrar->all(), $this->registrar->all());
        $this->assertCount(3, $this->registrar->all());
    }

    public function testGroupInstanceWithGroupMethod(): void
    {
        $commandGroup = $this->registrar->makeCommand('default');
        $this->assertInstanceOf(CommandGroup::class, $commandGroup);

        $eventGroup = $this->registrar->makeEvent('default');
        $this->assertInstanceOf(EventGroup::class, $eventGroup);

        $queryGroup = $this->registrar->makeQuery('default');
        $this->assertInstanceOf(QueryGroup::class, $queryGroup);

        $this->assertNotSame($this->registrar->all(), $this->registrar->all());
        $this->assertCount(3, $this->registrar->all());
    }

    public function testAddGroupNamesIntoSameGroup(): void
    {
        $defaultGroup = $this->registrar->make(DomainType::COMMAND, 'default');
        $anotherGroup = $this->registrar->make(DomainType::COMMAND, 'another');

        $this->assertNotSame($defaultGroup, $anotherGroup);

        $this->assertCount(1, $this->registrar->all());
    }

    public function testGetGroup(): void
    {
        $this->assertNull($this->registrar->get(DomainType::COMMAND, 'default'));

        $group = $this->registrar->make(DomainType::COMMAND, 'default');

        $this->assertEquals($group, $this->registrar->get(DomainType::COMMAND, 'default'));
    }

    public function testExceptionRaisedWhenGroupTypeAndNameAlreadyExists(): void
    {
        $this->expectException(RoutingViolation::class);
        $this->expectExceptionMessage('Group command already exists with name default');

        $this->registrar->make(DomainType::COMMAND, 'default');
        $this->registrar->make(DomainType::COMMAND, 'default');
    }

    public function testItSerializeGroups(): void
    {
        $this->registrar->make(DomainType::COMMAND, 'default_command_group');
        $this->registrar->make(DomainType::COMMAND, 'another_command_group');
        $this->registrar->make(DomainType::EVENT, 'default_event_group');
        $this->registrar->make(DomainType::QUERY, 'default_query_group');

        $groups = $this->registrar->all()->jsonSerialize();

        $this->assertCount(2, $groups['command']);
        $this->assertArrayHasKey('default_command_group', $groups['command']);
        $this->assertArrayHasKey('another_command_group', $groups['command']);

        $this->assertCount(1, $groups['event']);
        $this->assertArrayHasKey('default_event_group', $groups['event']);

        $this->assertCount(1, $groups['query']);
        $this->assertArrayHasKey('default_query_group', $groups['query']);
    }
}
