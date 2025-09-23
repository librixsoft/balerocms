<?php

namespace Framework\Rendering\Conditions;

class TruthyCondition implements ConditionInterface
{
    private string $key;

    public function __construct(string $key)
    {
        $this->key = $key;
    }

    public static function supports(string $expression): bool
    {
        return !empty($expression) && !preg_match('/[!=]/', $expression) && $expression[0] !== '!';
    }

    public static function fromExpression(string $expression): self
    {
        return new self($expression);
    }

    public function evaluate(array $flatParams): bool
    {
        return !empty($flatParams[$this->key] ?? null);
    }
}
