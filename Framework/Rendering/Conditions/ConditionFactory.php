<?php

namespace Framework\Rendering\Conditions;

/**
 * Fábrica de condiciones para parsing y evaluación de expresiones.
 *
 * @note OR y AND se pasan como **prototipos instanciados** y se clonan cuando se usan para evitar anomalias.
 * @note NotCondition, EqualsCondition, NotEqualsCondition y TruthyCondition se pasan
 *       como **nombres de clase** (strings) porque requieren parámetros en el constructor
 *       y se instancian mediante sus métodos estáticos `fromExpression()`.
 * Razón: estas clases tienen constructores que necesitan parámetros, y no queremos instanciarlas vacías.
 * La fábrica no necesita una instancia para probar si la clase soporta la expresión. Solo necesita llamar a sus métodos estáticos:
 */
class ConditionFactory
{
    /**
     * @var string[] Nombres de clase de condiciones simples.
     *
     * Estas clases deben tener métodos estáticos `supports()` y `fromExpression()`.
     */
    private array $conditionClasses;

    /**
     * @var OrCondition Prototipo de condición OR.
     *
     * Se clona cada vez que se necesita crear una nueva OR.
     */
    private OrCondition $orPrototype;

    /**
     * @var AndCondition Prototipo de condición AND.
     *
     * Se clona cada vez que se necesita crear una nueva AND.
     */
    private AndCondition $andPrototype;

    /**
     * Constructor.
     *
     * @param OrCondition $orPrototype Prototipo de OR.
     * @param AndCondition $andPrototype Prototipo de AND.
     * @param string[] $conditionClasses Nombres de clase de condiciones simples (Not, Equals, etc.)
     */
    public function __construct(
        OrCondition $orPrototype,
        AndCondition $andPrototype,
        array $conditionClasses = [
            NotCondition::class,
            EqualsCondition::class,
            NotEqualsCondition::class,
            TruthyCondition::class
        ]
    )
    {
        $this->orPrototype = $orPrototype;
        $this->andPrototype = $andPrototype;
        $this->conditionClasses = $conditionClasses;
    }

    /**
     * Parsea una expresión compleja con AND y OR.
     *
     * @param string $expression La expresión compleja a parsear.
     * @return ConditionInterface La condición raíz (OR) con toda la estructura de condiciones.
     */
    public function parseExpression(string $expression): ConditionInterface
    {
        $orCondition = $this->createOr();

        foreach (OrCondition::splitExpression($expression) as $orPart) {
            $andCondition = $this->createAnd();
            foreach (AndCondition::splitExpression($orPart) as $and) {
                $andCondition->addCondition($this->create(trim($and)));
            }
            $orCondition->addCondition($andCondition);
        }

        return $orCondition;
    }

    /**
     * Crea un nuevo OR clonando el prototipo inyectado.
     *
     * @return OrCondition
     */
    public function createOr(): OrCondition
    {
        return clone $this->orPrototype;
    }

    /**
     * Crea un nuevo AND clonando el prototipo inyectado.
     *
     * @return AndCondition
     */
    public function createAnd(): AndCondition
    {
        return clone $this->andPrototype;
    }

    /**
     * Crea una instancia de condición simple a partir de una expresión.
     *
     * @param string $expression La expresión a evaluar.
     * @return ConditionInterface La condición correspondiente.
     *
     * @throws \InvalidArgumentException Si no hay clase que soporte la expresión.
     */
    public function create(string $expression): ConditionInterface
    {
        $expression = trim($expression);

        foreach ($this->conditionClasses as $class) {
            if ($class::supports($expression)) {
                return $class::fromExpression($expression);
            }
        }

        throw new \InvalidArgumentException("Expresión no soportada: $expression");
    }
}
