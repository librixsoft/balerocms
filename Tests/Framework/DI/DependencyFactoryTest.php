<?php

declare(strict_types=1);

namespace Tests\Framework\DI;

use Framework\Attributes\Inject;
use Framework\DI\DependencyFactory;
use PHPUnit\Framework\TestCase;

final class DependencyFactoryTest extends TestCase
{
    public function testCreateWithConstructorAndPropertyInjection(): void
    {
        $resolver = new class {
            public function get(string $className): object
            {
                return new $className();
            }
        };

        $factory = new DependencyFactory($resolver);
        $obj = $factory->create(NeedsDeps::class);

        $this->assertInstanceOf(NeedsDeps::class, $obj);
        $this->assertInstanceOf(DepA::class, $obj->a);
        $this->assertInstanceOf(DepB::class, $obj->b);
    }

    public function testCreateWithoutConstructor(): void
    {
        $resolver = new class { public function get(string $c): object { return new $c(); } };
        $factory = new DependencyFactory($resolver);
        $obj = $factory->create(NoCtor::class);
        $this->assertInstanceOf(NoCtor::class, $obj);
    }
}

class DepA {}
class DepB {}
class NoCtor {}
class NeedsDeps {
    public DepA $a;
    #[Inject]
    public DepB $b;
    public function __construct(DepA $a){$this->a=$a;}
}
