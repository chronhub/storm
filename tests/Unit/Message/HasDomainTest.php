<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Message;

use Chronhub\Storm\Contracts\Reporter\Reporting;
use Chronhub\Storm\Reporter\DomainCommand;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Reporter\DomainQuery;
use Chronhub\Storm\Reporter\DomainType;
use Chronhub\Storm\Tests\Stubs\Double\SomeCommand;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\Stubs\Double\SomeQuery;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;

final class HasDomainTest extends UnitTestCase
{
    #[DataProvider('provideDomain')]
    public function testDomainTypeContent(Reporting $domain): void
    {
        $this->assertEmpty($domain->headers());

        $this->assertEquals(['foo' => 'bar'], $domain->toContent());

        if ($domain instanceof DomainCommand) {
            $this->assertEquals(DomainType::COMMAND, $domain->type());
        }

        if ($domain instanceof DomainEvent) {
            $this->assertEquals(DomainType::EVENT, $domain->type());
        }

        if ($domain instanceof DomainQuery) {
            $this->assertEquals(DomainType::QUERY, $domain->type());
        }
    }

    #[DataProvider('provideDomain')]
    public function testAddHeaderAndReturnNewInstance(Reporting $domain): void
    {
        $this->assertEmpty($domain->headers());

        $this->assertTrue($domain->hasNot('some'));
        $this->assertNull($domain->header('unknown'));

        $cloned = $domain->withHeader('name', 'steph bug');

        $this->assertNotSame($domain, $cloned);

        $this->assertTrue($cloned->has('name'));
        $this->assertNull($cloned->header('unknown header'));
        $this->assertEquals('steph bug', $cloned->header('name'));
        $this->assertEquals(['name' => 'steph bug'], $cloned->headers());
        $this->assertEquals(['foo' => 'bar'], $cloned->toContent());
    }

    #[DataProvider('provideDomain')]
    public function testAddManyHeadersAndReturnNewInstance(Reporting $domain): void
    {
        $this->assertEmpty($domain->headers());

        $this->assertTrue($domain->hasNot('name'));
        $this->assertNull($domain->header('unknown header'));

        $cloned = $domain->withHeaders(['name' => 'steph bug']);

        $this->assertNotEquals($domain, $cloned);

        $this->assertTrue($cloned->has('name'));
        $this->assertNull($cloned->header('unknown'));
        $this->assertEquals('steph bug', $cloned->header('name'));
        $this->assertEquals(['name' => 'steph bug'], $cloned->headers());
        $this->assertEquals(['foo' => 'bar'], $cloned->toContent());
    }

    public static function provideDomain(): Generator
    {
        $content = ['foo' => 'bar'];

        yield [SomeCommand::fromContent($content)];
        yield [SomeEvent::fromContent($content)];
        yield [SomeQuery::fromContent($content)];
    }
}
