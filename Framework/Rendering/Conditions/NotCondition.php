<?php

namespace Framework\Rendering\Conditions;

class NotCondition implements ConditionInterface
{
    private string $var = '';

    public function __construct()
    {
        // Constructor vacío compatible con DI
    }

    // Ahora es un método de instancia
    public function supports(string $expression): bool
    {
        return preg_match('/^!(.+)$/', trim($expression)) === 1;
    }

    // Método de instancia para inicializar a partir de expresión
    public function fromExpression(string $expression): self
    {
        if (!preg_match('/^!(.+)$/', trim($expression), $matches)) {
            throw new \InvalidArgumentException("Expresión inválida para NotCondition: $expression");
        }

        $this->var = $matches[1];
        return $this;
    }

    public function evaluate(array $params): bool
    {
        return empty($params[$this->var] ?? null);
    }
}
