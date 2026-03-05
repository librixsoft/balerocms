<?php

declare(strict_types=1);

namespace Tests\Framework\Testing;

use Framework\Testing\TestCase as FrameworkTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FrameworkTestCase::class)]
final class TestCaseBaseTest extends \PHPUnit\Framework\TestCase
{
    public function testFrameworkTestCaseIsAbstractAndExtendsPhpunitTestCase(): void
    {
        $r = new \ReflectionClass(FrameworkTestCase::class);

        $this->assertTrue($r->isAbstract());
        $this->assertTrue($r->isSubclassOf(\PHPUnit\Framework\TestCase::class));
    }

    public function testFrameworkTestCaseExposesHelperMethods(): void
    {
        $r = new \ReflectionClass(FrameworkTestCase::class);

        $this->assertTrue($r->hasMethod('getContainer'));
        $this->assertTrue($r->hasMethod('getMock'));
    }
}
