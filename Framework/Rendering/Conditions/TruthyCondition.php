<?php

namespace Framework\Rendering\Conditions;

class TruthyCondition implements ConditionInterface
{
    private ?string $key = null;

    // Constructor vacío compatible con DI
    public function __construct()
    {
    }

    // Método de instancia para verificar si la expresión aplica
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
        return !empty($flatParams[$this->key] ?? null);
    }
}
