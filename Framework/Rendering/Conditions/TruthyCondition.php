<?php

namespace Framework\Rendering\Conditions;

class TruthyCondition implements ConditionInterface
{
    private ?string $key = null;

    public function __construct()
    {
    }

    public function supports(string $expression): bool
    {
        return !empty($expression) && !preg_match('/[!=]/', $expression) && $expression[0] !== '!';
    }

    // Inicializa la instancia a partir de la expresión
    public function fromExpression(string $expression): self
    {
        $this->key = $expression;
        return $this;
    }

    public function evaluate(array $flatParams): bool
    {
        if (isset($flatParams[$this->key])) {
            return !empty($flatParams[$this->key]);
        }

        $prefix = $this->key . '.';
        foreach (array_keys($flatParams) as $param) {
            if (strpos($param, $prefix) === 0) {
                return true;
            }
        }

        return false;
    }
}