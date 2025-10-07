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

    /**
     * Resuelve y retorna una instancia de la clase solicitada
     *
     * Si la clase ya fue instanciada, retorna el singleton.
     * Si no existe, crea una nueva instancia resolviendo sus dependencias.
     *
     * @param string $className Nombre completo de la clase (FQCN)
     * @return object Instancia de la clase
     * @throws ContainerException Si no se puede resolver la dependencia
     */
    public function get(string $className): object
    {
        try {
            // Retornar singleton si existe
            if (isset($this->bindings[$className])) {
                return $this->bindings[$className];
            }

            // Crear nueva instancia usando DependencyFactory
            $factory = new DependencyFactory($this);
            return $factory->create($className);

        } catch (Throwable $e) {
            throw new ContainerException(
                "Error resolving {$className}: " . $e->getMessage(),
                0,
                $e
            );
        }
    }
}