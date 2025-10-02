<?php

/**
 * Balero CMS - Dependency Injection Container
 *
 * Provides automatic dependency resolution:
 * - Supports registering singleton instances via `set()`.
 * - Resolves classes recursively by inspecting their constructors.
 * - Injects properties marked with the #[Inject] attribute.
 *
 * Inspired by the principles of Inversion of Control (IoC).
 *
 * @author Anibal Gomez
 * @license GNU General Public License
 */

namespace Framework\DI;

use Closure;
use Framework\Exceptions\ContainerException;
use Throwable;

class Container
{
    /**
     * Map of identifiers to closures that return instances.
     *
     * @var array<string, Closure>
     */
    private array $bindings = [];

    /**
     * Registers a singleton instance for the given identifier.
     *
     * Every time this identifier is requested, the same registered
     * instance will be returned.
     *
     * @param string $id Class or interface name
     * @param object $instance Singleton instance
     * @return void
     */
    public function set(string $id, object $instance): void
    {
        $this->bindings[$id] = fn() => $instance;
    }

    /**
     * Resolves and returns an instance of the given class or interface.
     *
     * Resolution flow:
     * 1. If a binding or singleton is registered, return it.
     * 2. Otherwise, analyze the constructor and create dependencies automatically.
     * 3. After instantiation, automatically inject all properties
     *    marked with #[Inject] using container resolution.
     *
     * @template T
     * @param class-string<T> $id Class or interface name
     * @return T Resolved instance with constructor and property injection
     *
     * @throws ContainerException If the instance cannot be created or dependencies resolved
     */
    public function get(string $id): object
    {
        try {
            // No se pueden instanciar clases estáticas
            if (str_starts_with($id, 'Framework\\Static\\')) {
                throw new ContainerException("Static class {$id} cannot be instantiated");
            }

            // Retornar singleton si existe
            if (isset($this->bindings[$id])) {
                return ($this->bindings[$id])();
            }

            $reflector = new \ReflectionClass($id);
            if (!$reflector->isInstantiable()) {
                throw new ContainerException("Class {$id} is not instantiable");
            }

            // --- Crear instancia sin llamar al constructor ---
            $instance = $reflector->newInstanceWithoutConstructor();

            // --- Inyectar propiedades #[Inject] ---
            foreach ($reflector->getProperties() as $property) {
                $attributes = $property->getAttributes(\Framework\Attributes\Inject::class);
                if (!empty($attributes)) {
                    $propType = $property->getType();
                    if (!$propType instanceof \ReflectionNamedType || $propType->isBuiltin()) {
                        throw new ContainerException("Cannot inject property {$property->getName()} in {$id}");
                    }
                    $property->setAccessible(true);
                    $property->setValue($instance, $this->get($propType->getName()));
                }
            }

            // --- Llamar al constructor si existe ---
            $constructor = $reflector->getConstructor();
            if ($constructor) {
                $dependencies = [];
                foreach ($constructor->getParameters() as $param) {
                    $type = $param->getType();
                    if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
                        throw new ContainerException("Cannot resolve parameter {$param->getName()} in {$id}");
                    }
                    $dependencies[] = $this->get($type->getName());
                }
                $constructor->invokeArgs($instance, $dependencies);
            }

            return $instance;

        } catch (\Throwable $e) {
            throw new ContainerException(
                "Error while resolving dependency for {$id}: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

}
