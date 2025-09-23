<?php

namespace Framework\Rendering\Conditions;

class NotEqualsCondition implements ConditionInterface
{
    private string $var1;
    private string $var2;
    private bool $isLiteral;

    public function __construct(string $var1, string $var2, bool $isLiteral = false)
    {
        $this->var1 = $var1;
        $this->var2 = $var2;
        $this->isLiteral = $isLiteral;
    }

    public static function supports(string $expression): bool
    {
        return preg_match('/^[\w\.]+\s*!=\s*[\'"]?[\w\.]+[\'"]?$/', $expression) === 1;
    }

    public static function fromExpression(string $expression): ConditionInterface
    {
        if (!preg_match('/^([\w\.]+)\s*!=\s*([\'"]?)([\w\.]+)\2$/', $expression, $matches)) {
            throw new \InvalidArgumentException("Expresión no válida para NotEqualsCondition: $expression");
        }

        $var1 = $matches[1];
        $var2 = $matches[3];
        $isLiteral = $matches[2] !== '';

        return new self($var1, $var2, $isLiteral);
    }

    public function evaluate(array $params): bool
    {
        $val1 = $params[$this->var1] ?? null;
        $val2 = $this->isLiteral ? $this->var2 : ($params[$this->var2] ?? null);

        return strcasecmp((string)$val1, (string)$val2) !== 0;
    }
}
