<?php

declare(strict_types=1);

namespace Tests\Framework\Rendering\Conditions;

use Framework\Rendering\Conditions\AndCondition;
use Framework\Rendering\Conditions\ConditionFactory;
use Framework\Rendering\Conditions\EqualsCondition;
use Framework\Rendering\Conditions\NotCondition;
use Framework\Rendering\Conditions\NotEqualsCondition;
use Framework\Rendering\Conditions\OrCondition;
use Framework\Rendering\Conditions\TruthyCondition;
use PHPUnit\Framework\TestCase;

final class ConditionFactoryTest extends TestCase
{
    private function makeFactory(): ConditionFactory
    {
        return new ConditionFactory(
            new OrCondition(),
            new AndCondition(),
            new NotCondition(),
            new EqualsCondition(),
            new NotEqualsCondition(),
            new TruthyCondition()
        );
    }

    public function testCreateAndParseExpression(): void
    {
        $f = $this->makeFactory();

        $this->assertTrue($f->create('a == b')->evaluate(['a' => 'x', 'b' => 'x']));

        $expr = $f->parseExpression("role == 'admin' OR !disabled");
        $this->assertTrue($expr->evaluate(['role' => 'admin', 'disabled' => true]));
        $this->assertTrue($expr->evaluate(['role' => 'user', 'disabled' => null]));
    }

    public function testCreateThrowsForUnsupportedExpression(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->makeFactory()->create('');
    }
}
