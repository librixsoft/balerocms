<?php

declare(strict_types=1);

namespace Tests\Framework\Testing;

use Framework\Testing\TestCase as FrameworkTestCase;
use PHPUnit\Framework\TestCase;

final class TestCaseTest extends TestCase
{
    public function testFrameworkTestCaseShape(): void
    {
        $r = new \ReflectionClass(FrameworkTestCase::class);

        $this->assertTrue($r->isAbstract());
        $this->assertTrue($r->isSubclassOf(\PHPUnit\Framework\TestCase::class));
        $this->assertTrue($r->hasMethod('setUp'));
        $this->assertTrue($r->hasMethod('getContainer'));
        $this->assertTrue($r->hasMethod('getMock'));
    }
}
