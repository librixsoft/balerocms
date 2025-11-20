<?php

namespace Framework\DI;

use Framework\Exceptions\ContainerException;
use Throwable;

/**
 * Contenedor de Inyección de Dependencias
 *
 * Responsabilidad única: resolver y cachear instancias de clases.
 * NO contiene lógica de negocio (routing, controllers, etc).
 */
class Container
{
    /** @var array<string, object> Cache de instancias singleton */
    private array $bindings = [];

    /** @var array<string, callable(Container): object> Factories para crear instancias */
    private array $factories = [];

    public function __construct()
    {
        $this->bindings = [];
        $this->factories = [];
    }

    /**
     * Registra una instancia singleton en el contenedor
     *
     * @template T of object
     * @param class-string<T> $id Identificador de la clase (FQCN)
     * @param T $instance Instancia a registrar
     * @return void
     */
    public function set(string $id, object $instance): void
    {
        $this->bindings[$id] = $instance;
    }

    /**
     * Registra una factory para un singleton
     *
     * @template T of object
     * @param class-string<T> $id
     * @param callable(Container): T $factory
     * @return void
     */
    public function singleton(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
    }

    /**
     * Obtiene una instancia del contenedor, resolviendo automáticamente
     *
     * @template T of object
     * @param class-string<T> $className
     * @return T
     * @throws ContainerException
     */
    public function get(string $className): object
    {
        try {
            if ($className === Container::class || $className === self::class) {
                return $this;
            }

            if (isset($this->bindings[$className])) {
                /** @var T $instance */
                $instance = $this->bindings[$className];
                return $instance;
            }

            if (isset($this->factories[$className])) {
                /** @var T $instance */
                $instance = ($this->factories[$className])($this);
                $this->bindings[$className] = $instance;
                return $instance;
            }

            $factory = new DependencyFactory($this);
            /** @var T $instance */
            $instance = $factory->create($className);
            $this->bindings[$className] = $instance;

            return $instance;

        } catch (Throwable $e) {
            throw new ContainerException(
                "Error resolving {$className}: " . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
