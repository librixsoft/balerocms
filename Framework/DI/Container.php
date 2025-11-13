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

    /** @var array<string, callable> Factories para crear instancias */
    private array $factories = [];

    public function __construct()
    {
        $this->bindings = [];
        $this->factories = [];
    }

    /**
     * Registra una instancia singleton en el contenedor
     *
     * @param string $id Identificador de la clase (FQCN)
     * @param object $instance Instancia a registrar
     */
    public function set(string $id, object $instance): void
    {


        $this->bindings[$id] = $instance;
    }

    public function singleton(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
    }

    public function get(string $className): object
    {
        try {
            // Si piden el Container, retornar esta instancia
            if ($className === Container::class || $className === self::class) {
                return $this;
            }

            // Retornar singleton si existe
            if (isset($this->bindings[$className])) {
                return $this->bindings[$className];
            }

            // Si existe una factory, ejecutarla y cachear
            if (isset($this->factories[$className])) {
                $instance = ($this->factories[$className])($this);
                $this->bindings[$className] = $instance;
                return $instance;
            }

            // Crear nueva instancia usando DependencyFactory Y CACHEARLA
            $factory = new DependencyFactory($this);
            $instance = $factory->create($className);

            // CACHEAR la instancia para futuras peticiones
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