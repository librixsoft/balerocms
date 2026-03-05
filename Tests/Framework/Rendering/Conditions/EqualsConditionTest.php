<?php

declare(strict_types=1);

namespace Tests\Framework\Rendering\Conditions;

use Framework\Rendering\Conditions\EqualsCondition;
use PHPUnit\Framework\TestCase;

final class EqualsConditionTest extends TestCase
{
    public function testSupportsAndEvaluateVariableComparison(): void
    {
        $c = (new EqualsCondition())->fromExpression('name == other');
        $this->assertTrue((new EqualsCondition())->supports('name == other'));
        $this->assertTrue($c->evaluate(['name' => 'John', 'other' => 'john']));
    }

    public function testEvaluateLiteralComparisonAndInvalidExpression(): void
    {
        $c = (new EqualsCondition())->fromExpression("status == 'active'");
        $this->assertTrue($c->evaluate(['status' => 'ACTIVE']));

        $this->expectException(\InvalidArgumentException::class);
        (new EqualsCondition())->fromExpression('invalid expression');
    }
}
