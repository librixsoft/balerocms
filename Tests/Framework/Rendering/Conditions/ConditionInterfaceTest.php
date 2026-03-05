<?php

declare(strict_types=1);

namespace Tests\Framework\Rendering\Conditions;

use Framework\Rendering\Conditions\ConditionInterface;
use PHPUnit\Framework\TestCase;

final class ConditionInterfaceTest extends TestCase
{
    public function testInterfaceDefinesRequiredMethods(): void
    {
        $r = new \ReflectionClass(ConditionInterface::class);

        $this->assertTrue($r->isInterface());
        $this->assertTrue($r->hasMethod('supports'));
        $this->assertTrue($r->hasMethod('fromExpression'));
        $this->assertTrue($r->hasMethod('evaluate'));
    }
}
