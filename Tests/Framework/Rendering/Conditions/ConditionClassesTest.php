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

final class ConditionClassesTest extends TestCase
{
    public function testAndAndOrSplitExpressionSupportSymbolsAndWords(): void
    {
        $this->assertSame(['a', 'b', 'c'], AndCondition::splitExpression('a && b AND c'));
        $this->assertSame(['a', 'b', 'c'], OrCondition::splitExpression('a || b OR c'));
    }

    public function testEqualsConditionSupportsFromExpressionAndEvaluate(): void
    {
        $c = new EqualsCondition();
        $this->assertTrue($c->supports("status == 'ok'"));
        $this->assertTrue($c->fromExpression("status == 'ok'")->evaluate(['status' => 'OK']));
        $this->assertFalse($c->fromExpression('a == b')->evaluate(['a' => 'x', 'b' => 'y']));
    }

    public function testNotEqualsConditionSupportsFromExpressionAndEvaluate(): void
    {
        $c = new NotEqualsCondition();
        $this->assertTrue($c->supports("status != 'ok'"));
        $this->assertTrue($c->fromExpression('a != b')->evaluate(['a' => 'x', 'b' => 'y']));
        $this->assertFalse($c->fromExpression("status != 'ok'")->evaluate(['status' => 'OK']));
    }

    public function testNotConditionSupportsFromExpressionAndEvaluate(): void
    {
        $c = new NotCondition();
        $this->assertTrue($c->supports('!enabled'));
        $this->assertTrue($c->fromExpression('!enabled')->evaluate(['enabled' => '']));
        $this->assertFalse($c->fromExpression('!enabled')->evaluate(['enabled' => '1']));
    }

    public function testTruthyConditionEvaluateWithDirectKeyAndNestedPrefix(): void
    {
        $c = new TruthyCondition();
        $this->assertTrue($c->supports('user'));
        $this->assertTrue($c->fromExpression('user')->evaluate(['user' => 'yes']));
        $this->assertTrue($c->fromExpression('profile')->evaluate(['profile.name' => 'Ada']));
        $this->assertFalse($c->fromExpression('missing')->evaluate(['foo' => 'bar']));
    }

    public function testConditionFactoryCreateAndParseExpression(): void
    {
        $factory = new ConditionFactory(
            new OrCondition(),
            new AndCondition(),
            new NotCondition(),
            new EqualsCondition(),
            new NotEqualsCondition(),
            new TruthyCondition()
        );

        $this->assertTrue($factory->create('a')->evaluate(['a' => 1]));

        $expr = $factory->parseExpression("status == 'ok' && !blocked || admin");
        $this->assertTrue($expr->evaluate(['status' => 'OK', 'blocked' => '', 'admin' => false]));
        $this->assertFalse($expr->evaluate(['status' => 'fail', 'blocked' => '', 'admin' => false]));

        $this->expectException(\InvalidArgumentException::class);
        $factory->create('a >= b');
    }
}
