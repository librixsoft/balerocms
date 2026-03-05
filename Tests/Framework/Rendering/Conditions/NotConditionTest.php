<?php

declare(strict_types=1);

namespace Tests\Framework\Rendering\Conditions;

use Framework\Rendering\Conditions\NotCondition;
use PHPUnit\Framework\TestCase;

final class NotConditionTest extends TestCase
{
    public function testSupportsFromExpressionAndEvaluate(): void
    {
        $c = new NotCondition();
        $this->assertTrue($c->supports('!flag'));

        $c->fromExpression('!flag');
        $this->assertTrue($c->evaluate(['flag' => null]));
        $this->assertFalse($c->evaluate(['flag' => 'on']));
    }

    public function testFromExpressionThrowsOnInvalidInput(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new NotCondition())->fromExpression('flag');
    }
}
