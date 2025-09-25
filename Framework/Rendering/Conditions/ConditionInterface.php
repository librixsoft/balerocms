<?php

namespace Framework\Rendering\Conditions;

interface ConditionInterface
{
    /**
     * Indica si esta condición puede manejar la expresión dada.
     */
    public function supports(string $expression): bool;

    /**
     * Inicializa la condición a partir de la expresión.
     *
     * @param string $expression
     * @return self
     */
    public function fromExpression(string $expression): self;

    /**
     * Evalúa la condición usando los parámetros planos.
     *
     * @param array $flatParams
     * @return bool
     */
    public function evaluate(array $flatParams): bool;
}
