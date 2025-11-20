<?php

namespace Framework\Rendering\Conditions;

class AndCondition implements ConditionInterface
{
    private array $conditions = [];

    /**
     * Helper para dividir la expresión por AND o &&.
     */
    public static function splitExpression(string $expression): array
    {
        // Soportar tanto && como AND (case insensitive)
        return preg_split('/\s*&&\s*|\s+AND\s+/i', $expression);
    }

    /**
     * Esta condición siempre se maneja en la factory como AND compuesto,
     * no se usa para parsear expresiones simples.
     */
    public function supports(string $expression): bool
    {
        return false; // Nunca "aplica" a una expresión simple
    }

    /**
     * Inicialización desde expresión (no hace nada, AND no se inicializa así)
     */
    public function fromExpression(string $expression): self
    {
        // AND no necesita inicialización a partir de una expresión simple
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
     * Evalúa las condiciones AND.
     */
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