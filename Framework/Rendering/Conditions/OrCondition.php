<?php

namespace Framework\Rendering\Conditions;

class OrCondition implements ConditionInterface
{
    private array $conditions = [];

    /**
     * Helper para dividir la expresión por OR o ||.
     */
    public static function splitExpression(string $expression): array
    {
        return preg_split('/\s*\|\|\s*|\s+OR\s+/i', $expression);
    }

    /**
     * Esta condición siempre se maneja en la factory como OR compuesto,
     * no se usa para parsear expresiones simples.
     */
    public function supports(string $expression): bool
    {
        return false; // Nunca "aplica" a una expresión simple
    }

    /**
     * Inicialización desde expresión (no hace nada, OR no se inicializa así)
     */
    public function fromExpression(string $expression): self
    {
        // OR no necesita inicialización a partir de una expresión simple
        return $this;
    }

    /**
     * Agrega una condición hija.
     */
    public function addCondition(ConditionInterface $condition): void
    {
        $this->conditions[] = $condition;
    }

    /**
     * Evalúa las condiciones OR.
     */
    public function evaluate(array $params): bool
    {
        foreach ($this->conditions as $condition) {
            if ($condition->evaluate($params)) {
                return true;
            }
        }
        return false;
    }
}