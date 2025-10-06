<?php

/**
 * Balero CMS - Dependency Injection Container
 *
 * Provee resolución automática de dependencias:
 * - Permite registrar instancias singleton mediante `set()`.
 * - Resuelve clases recursivamente inspeccionando sus constructores.
 * - Inyecta propiedades marcadas con el atributo #[Inject].
 *
 * Inspirado en los principios de Inversion of Control (IoC).
 *
 * @author Anibal Gomez <balerocms@gmail.com>
 * @license GNU General Public License
 */

namespace Framework\DI;

use Framework\Attributes\Controller;
use Framework\Core\BaseController;
use Framework\Exceptions\ContainerException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use Throwable;

class Container
{
    /**
     * Mapa de identificadores a instancias singleton
     *
     * @var array<string, object>
     */
    private array $bindings = [];

    /**
     * Registra una instancia singleton para un identificador
     *
     * Cada vez que se solicite este identificador, se retornará
     * la misma instancia registrada.
     *
     * @param string $id Nombre de la clase o interfaz
     * @param object $instance Instancia singleton
     * @return void
     */
    public function set(string $id, object $instance): void
    {
        $this->bindings[$id] = $instance;
    }

    /**
     * Resuelve y retorna una instancia de la clase o interfaz indicada.
     *
     * Flujo de resolución:
     * 1. Si existe un singleton registrado, retorna esa instancia.
     * 2. Analiza el constructor y crea las dependencias automáticamente.
     * 3. Después de la instanciación, inyecta todas las propiedades
     *    marcadas con #[Inject] usando resolución del container.
     *
     * @template T
     * @param class-string<T> $className Nombre de la clase o interfaz
     * @return T Instancia resuelta con constructor y propiedades inyectadas
     * @throws ContainerException Si no se puede crear la instancia o resolver dependencias
     */
    public function get(string $className): object
    {
        try {
            if (isset($this->bindings[$className])) {
                return $this->bindings[$className];
            }

            $reflector = new ReflectionClass($className);
            if (!$reflector->isInstantiable()) {
                throw new ContainerException("Class {$className} no es instanciable");
            }

            $constructor = $reflector->getConstructor();
            $instance = ($constructor && $constructor->getNumberOfParameters() > 0)
                ? $this->createWithConstructor($reflector, $constructor)
                : $reflector->newInstance();

            // --- Inyección de propiedades #[Inject] ---
            foreach ($reflector->getProperties() as $prop) {
                $attrs = $prop->getAttributes(\Framework\Attributes\Inject::class);
                if ($attrs) {
                    $type = $prop->getType();
                    if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                        $prop->setAccessible(true);
                        $prop->setValue($instance, $this->get($type->getName()));
                    }
                }
            }

            // --- Lógica de Controller ---
            $controllerAttrs = $reflector->getAttributes(Controller::class);
            $classNamespace = $reflector->getName();
            if (!empty($controllerAttrs) && str_starts_with($classNamespace, 'App')) {
                $baseController = $this->get(BaseController::class);
                $method = new ReflectionMethod(BaseController::class, 'initControllerAndRoute');
                $method->invoke($baseController, $instance);
            }

            return $instance;
        } catch (Throwable $e) {
            throw new ContainerException("Error resolviendo {$className}: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Crea una instancia de la clase usando inyección por constructor
     *
     * @param ReflectionClass $reflector Reflección de la clase a instanciar
     * @param ReflectionMethod $constructor Método constructor de la clase
     * @return object Instancia creada con dependencias resueltas
     */
    private function createWithConstructor(ReflectionClass $reflector, ReflectionMethod $constructor): object
    {
        $params = [];
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();
            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                $params[] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
            } else {
                $params[] = $this->get($type->getName());
            }
        }
        return $reflector->newInstanceArgs($params);
    }
}
