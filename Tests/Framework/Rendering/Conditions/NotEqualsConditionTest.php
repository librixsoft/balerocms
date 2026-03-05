<?php

declare(strict_types=1);

namespace Tests\Framework\Rendering\Conditions;

use Framework\Rendering\Conditions\NotEqualsCondition;
use PHPUnit\Framework\TestCase;

final class NotEqualsConditionTest extends TestCase
{
    public function testSupportsAndEvaluateVariableAndLiteral(): void
    {
        $c1 = (new NotEqualsCondition())->fromExpression('a != b');
        $this->assertTrue((new NotEqualsCondition())->supports('a != b'));
        $this->assertTrue($c1->evaluate(['a' => 'x', 'b' => 'y']));

        $c2 = (new NotEqualsCondition())->fromExpression("status != 'active'");
        $this->assertFalse($c2->evaluate(['status' => 'ACTIVE']));
    }

    public function testInvalidExpressionThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new NotEqualsCondition())->fromExpression('a <> b');
    }
}
