<?php

declare(strict_types=1);

namespace Tests\Framework\DI;

use Framework\Attributes\InjectMocks;
use Framework\DI\TestContainer;
use PHPUnit\Framework\TestCase;

final class TestContainerTest extends TestCase
{
    #[InjectMocks]
    private SUTForTC $sut;

    public function testInitTestAndGetMockCache(): void
    {
        $tc = new TestContainer($this);
        $tc->initTest($this);

        $this->assertInstanceOf(SUTForTC::class, $this->sut);

        $m1 = $tc->get(D1::class);
        $m2 = $tc->get(D1::class);
        $this->assertSame($m1, $m2);
        $this->assertSame($m1, $tc->getMock(D1::class));
    }
}

class D1 {}
class SUTForTC { public function __construct(public D1 $d1) {} }
