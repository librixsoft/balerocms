<?php

declare(strict_types=1);

namespace Tests\Framework\Rendering\Conditions;

use Framework\Rendering\Conditions\AndCondition;
use Framework\Rendering\Conditions\ConditionInterface;
use PHPUnit\Framework\TestCase;

final class AndConditionTest extends TestCase
{
    public function testSplitExpressionSupportsAndAndDoubleAmpersand(): void
    {
        $this->assertSame(['a', 'b', 'c'], AndCondition::splitExpression('a && b AND c'));
    }

    public function testEvaluateReturnsTrueOnlyWhenAllChildConditionsPass(): void
    {
        $cond = new AndCondition();
        $ok = new class implements ConditionInterface {
            public function supports(string $expression): bool { return true; }
            public function fromExpression(string $expression): self { return $this; }
            public function evaluate(array $params): bool { return true; }
        };
        $fail = new class implements ConditionInterface {
            public function supports(string $expression): bool { return true; }
            public function fromExpression(string $expression): self { return $this; }
            public function evaluate(array $params): bool { return false; }
        };

        $cond->addCondition($ok);
        $cond->addCondition($fail);

        $this->assertFalse($cond->evaluate([]));
    }
}
