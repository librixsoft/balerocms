<?php

namespace Framework\Rendering\Conditions;

class ConditionFactory
{
    private OrCondition $orPrototype;
    private AndCondition $andPrototype;

    /** @var ConditionInterface[] */
    private array $conditions;

    public function __construct(
        OrCondition $orPrototype,
        AndCondition $andPrototype,
        NotCondition $notCondition,
        EqualsCondition $equalsCondition,
        NotEqualsCondition $notEqualsCondition,
        TruthyCondition $truthyCondition
    ) {
        $this->orPrototype = $orPrototype;
        $this->andPrototype = $andPrototype;

        // Guardamos todas las condiciones en un array interno
        $this->conditions = [
            $notCondition,
            $equalsCondition,
            $notEqualsCondition,
            $truthyCondition
        ];
    }

    public function parseExpression(string $expression): ConditionInterface
    {
        $orCondition = clone $this->orPrototype;

        foreach (OrCondition::splitExpression($expression) as $orPart) {
            $andCondition = clone $this->andPrototype;
            foreach (AndCondition::splitExpression($orPart) as $and) {
                $andCondition->addCondition($this->create(trim($and)));
            }
            $orCondition->addCondition($andCondition);
        }

        return $orCondition;
    }

    public function create(string $expression): ConditionInterface
    {
        foreach ($this->conditions as $condition) {
            if ($condition->supports($expression)) {
                return (clone $condition)->fromExpression($expression);
            }
        }

        throw new \InvalidArgumentException("Expresión no soportada: $expression");
    }

    public function createOr(): OrCondition
    {
        return clone $this->orPrototype;
    }

    public function createAnd(): AndCondition
    {
        return clone $this->andPrototype;
    }
}
