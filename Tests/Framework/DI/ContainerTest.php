<?php

declare(strict_types=1);

namespace Tests\Framework\DI;

use Framework\DI\Container;
use Framework\Exceptions\ContainerException;
use PHPUnit\Framework\TestCase;

final class ContainerTest extends TestCase
{
    public function testSetSingletonAndSelfResolution(): void
    {
        $c = new Container();

        $obj = new \stdClass();
        $c->set(\stdClass::class, $obj);
        $this->assertSame($obj, $c->get(\stdClass::class));

        $c->singleton(DateTimeObject::class, fn(Container $ctn) => new DateTimeObject('ok'));
        $resolved1 = $c->get(DateTimeObject::class);
        $resolved2 = $c->get(DateTimeObject::class);

        $this->assertSame('ok', $resolved1->value);
        $this->assertSame($resolved1, $resolved2);
        $this->assertSame($c, $c->get(Container::class));
    }

    public function testGetWrapsResolutionErrorsInContainerException(): void
    {
        $this->expectException(ContainerException::class);
        (new Container())->get('Class\\That\\DoesNotExist');
    }
}

class DateTimeObject
{
    public function __construct(public string $value) {}
}
