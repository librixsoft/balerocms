<?php

declare(strict_types=1);

namespace Tests\Framework\Rendering\Conditions;

use Framework\Rendering\Conditions\ConditionInterface;
use Framework\Rendering\Conditions\OrCondition;
use PHPUnit\Framework\TestCase;

final class OrConditionTest extends TestCase
{
    public function testSplitExpressionAndEvaluate(): void
    {
        $this->assertSame(['a', 'b', 'c'], OrCondition::splitExpression('a || b OR c'));

        $or = new OrCondition();
        $falseCond = new class implements ConditionInterface {
            public function supports(string $expression): bool { return true; }
            public function fromExpression(string $expression): self { return $this; }
            public function evaluate(array $flatParams): bool { return false; }
        };
        $trueCond = new class implements ConditionInterface {
            public function supports(string $expression): bool { return true; }
            public function fromExpression(string $expression): self { return $this; }
            public function evaluate(array $flatParams): bool { return true; }
        };

        $or->addCondition($falseCond);
        $or->addCondition($trueCond);

        $this->assertTrue($or->evaluate([]));
    }

    public function testEvaluateReturnsFalseWhenAllFail(): void
    {
        $or = new OrCondition();
        $falseCond = new class implements ConditionInterface {
            public function supports(string $expression): bool { return true; }
            public function fromExpression(string $expression): self { return $this; }
            public function evaluate(array $flatParams): bool { return false; }
        };

        $or->addCondition($falseCond);
        $or->addCondition($falseCond);

        $this->assertFalse($or->evaluate([]));
    }

    public function testEvaluateReturnsFalseWhenEmpty(): void
    {
        $or = new OrCondition();
        $this->assertFalse($or->evaluate([]));
    }
    
    public function testSupportsAlwaysReturnsFalse(): void
    {
        $or = new OrCondition();
        $this->assertFalse($or->supports('anything'));
    }

    public function testFromExpressionReturnsSelf(): void
    {
        $or = new OrCondition();
        $this->assertSame($or, $or->fromExpression('anything'));
    }
}
