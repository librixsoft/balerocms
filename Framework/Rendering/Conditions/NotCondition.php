<?php

namespace Framework\Rendering\Conditions;

class NotCondition implements ConditionInterface
{
    private string $var;

    public function __construct(string $var)
    {
        $this->var = $var;
    }

    // ✅ Método estático para que el factory lo use sin instanciar
    public static function supports(string $expression): bool
    {
        return preg_match('/^!(.+)$/', trim($expression)) === 1;
    }

    // ✅ Crea la instancia a partir de la expresión
    public static function fromExpression(string $expression): self
    {
        if (!preg_match('/^!(.+)$/', trim($expression), $matches)) {
            throw new \InvalidArgumentException("Expresión inválida para NotCondition: $expression");
        }

        return new self($matches[1]);
    }

    public function evaluate(array $params): bool
    {
        return empty($params[$this->var] ?? null);
    }
}
