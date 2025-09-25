<?php

namespace Framework\Rendering\Conditions;

class NotEqualsCondition implements ConditionInterface
{
    private ?string $var1 = null;
    private ?string $var2 = null;
    private bool $isLiteral = false;

    // Constructor vacío compatible con DI
    public function __construct()
    {
    }

    // Método de instancia para verificar si la expresión aplica
    public function supports(string $expression): bool
    {
        return preg_match('/^[\w\.]+\s*!=\s*[\'"]?[\w\.]+[\'"]?$/', $expression) === 1;
    }

    // Inicializa la instancia a partir de la expresión
    public function fromExpression(string $expression): self
    {
        if (!preg_match('/^([\w\.]+)\s*!=\s*([\'"]?)([\w\.]+)\2$/', $expression, $matches)) {
            throw new \InvalidArgumentException("Expresión no válida para NotEqualsCondition: $expression");
        }

        $this->var1 = $matches[1];
        $this->var2 = $matches[3];
        $this->isLiteral = $matches[2] !== '';

        return $this;
    }

    public function evaluate(array $params): bool
    {
        $val1 = $params[$this->var1] ?? null;
        $val2 = $this->isLiteral ? $this->var2 : ($params[$this->var2] ?? null);

        return strcasecmp((string)$val1, (string)$val2) !== 0;
    }
}
