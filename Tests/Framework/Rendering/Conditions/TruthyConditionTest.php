<?php

declare(strict_types=1);

namespace Tests\Framework\Rendering\Conditions;

use Framework\Rendering\Conditions\TruthyCondition;
use PHPUnit\Framework\TestCase;

final class TruthyConditionTest extends TestCase
{
    public function testSupportsFromExpressionAndEvaluate(): void
    {
        $c = (new TruthyCondition())->fromExpression('user');

        $this->assertTrue((new TruthyCondition())->supports('user'));
        $this->assertFalse((new TruthyCondition())->supports('a == b'));
        $this->assertTrue($c->evaluate(['user' => 'john']));
        $this->assertFalse($c->evaluate(['user' => '']));
        $this->assertTrue($c->evaluate(['user.name' => 'john']));
    }
}
