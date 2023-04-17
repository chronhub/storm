<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Message;

use Chronhub\Storm\Message\HasConstructableContent;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;

final class HasConstructableContentTest extends UnitTestCase
{
    public function testInstance(): void
    {
        $someContent = $this->newInstance();

        $this->assertEmpty($someContent->toContent());
        $this->assertEmpty($someContent->content);
    }

    public function testInstanceWithContent(): void
    {
        $someContent = $this->newInstance(['name' => 'steph bug']);

        $this->assertEquals(['name' => 'steph bug'], $someContent->toContent());
        $this->assertEquals(['name' => 'steph bug'], $someContent->content);
    }

    public function testStaticInstance(): void
    {
        $someContent = $this->newInstance()::fromContent(['name' => 'steph bug']);

        $this->assertEquals(['name' => 'steph bug'], $someContent->toContent());
        $this->assertEquals(['name' => 'steph bug'], $someContent->content);
    }

    public function testStaticInstanceReturnNewInstance(): void
    {
        $someDomain = (new SomeEvent(['name' => 'steph bug']))->withHeaders(['foo' => 'bar']);

        $newDomain = $someDomain::fromContent(['name' => 'steph']);

        $this->assertNotSame($someDomain, $newDomain);

        $this->assertEquals(['name' => 'steph bug'], $someDomain->toContent());
        $this->assertEquals(['foo' => 'bar'], $someDomain->headers());
        $this->assertEquals([], $newDomain->headers());
    }

    public function newInstance(array $content = []): object
    {
        return new class($content)
        {
            use HasConstructableContent;
        };
    }
}
