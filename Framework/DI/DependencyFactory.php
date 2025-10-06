<?php

/**
 * Balero CMS - Dependency Factory
 *
 * Centraliza la creación de instancias de clases, soportando:
 * - Inyección por constructor
 * - Inyección de propiedades marcadas con #[Inject]
 * - Resolución delegada a un contenedor que implementa `get(string $className): object`
 *
 * @author Anibal Gomez <balerocms@gmail.com>
 * @license GNU General Public License
 */

namespace Framework\DI;

use ReflectionClass;
use ReflectionNamedType;
use ReflectionMethod;
use Framework\Attributes\Inject;

class DependencyFactory
{
    /**
     * Contenedor que implementa `resolve(string $className): object`
     *
     * @var object
     */
    private object $resolverContainer;

    /**
     * Constructor
     *
     * @param object $resolverContainer Contenedor con método resolve()
     */
    public function __construct(object $resolverContainer)
    {
        $this->resolverContainer = $resolverContainer;
    }

    /**
     * Crea una instancia de clase con inyección por constructor
     *
     * @param ReflectionClass $reflector Reflección de la clase
     * @param ReflectionMethod $constructor Constructor de la clase
     * @return object Instancia creada con dependencias resueltas
     */
    public function createWithConstructor(ReflectionClass $reflector, ReflectionMethod $constructor): object
    {
        $params = [];
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();
            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                $params[] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
            } else {
                $params[] = $this->resolverContainer->get($type->getName());
            }
        }
        return $reflector->newInstanceArgs($params);
    }

    /**
     * Crea una instancia de clase sin pasar parámetros al constructor
     *
     * @param ReflectionClass $reflector Reflección de la clase
     * @return object Instancia creada sin argumentos
     */
    public function createWithoutConstructor(ReflectionClass $reflector): object
    {
        return $reflector->newInstance();
    }

    /**
     * Inyecta dependencias en propiedades marcadas con #[Inject]
     *
     * @param object $instance Instancia de la clase
     * @param ReflectionClass $reflector Reflección de la clase
     * @return void
     */
    public function injectProperties(object $instance, ReflectionClass $reflector): void
    {
        foreach ($reflector->getProperties() as $prop) {
            $attrs = $prop->getAttributes(Inject::class);
            if (empty($attrs)) continue;

            $type = $prop->getType();
            if (!$type instanceof ReflectionNamedType) continue;

            $prop->setAccessible(true);
            $prop->setValue($instance, $this->resolverContainer->get($type->getName()));
        }
    }

    /**
     * Crea la instancia completa de la clase (constructor + propiedades #[Inject])
     *
     * @param string $className Nombre de la clase a crear
     * @return object Instancia creada con todas las dependencias
     */
    public function create(string $className): object
    {
        $reflector = new ReflectionClass($className);
        $constructor = $reflector->getConstructor();

        $instance = ($constructor && $constructor->getNumberOfParameters() > 0)
            ? $this->createWithConstructor($reflector, $constructor)
            : $this->createWithoutConstructor($reflector);

        $this->injectProperties($instance, $reflector);

        return $instance;
    }
}
