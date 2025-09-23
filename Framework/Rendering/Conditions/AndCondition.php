<?php

namespace Framework\Rendering\Conditions;

class AndCondition implements ConditionInterface
{
    private array $conditions = [];

    public static function splitExpression(string $expression): array
    {
        return preg_split('/\s+AND\s+/i', $expression);
    }

    public static function supports(string $expression): bool
    {
        return false;
    }

    public function addCondition(ConditionInterface $condition): void
    {
        $this->conditions[] = $condition;
    }

    public function evaluate(array $params): bool
    {
        foreach ($this->conditions as $condition) {
            if (!$condition->evaluate($params)) {
                return false;
            }
        }
        return true;
    }
}
