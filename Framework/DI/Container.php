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
     * @param string $id Nombre de la clase o interfaz
     * @param object $instance Instancia singleton
     * @return void
     */
    public function set(string $id, object $instance): void
    {
        $this->bindings[$id] = $instance;
    }


    /**
     * Método interno que hace la resolución real de la clase
     *
     * @param string $className
     * @return object
     */
    public function get(string $className): object
    {
        if (isset($this->bindings[$className])) {
            return $this->bindings[$className];
        }

        $factory = new DependencyFactory($this);
        $instance = $factory->create($className);

        // --- Lógica de Controller ---
        $reflector = new ReflectionClass($className);
        $controllerAttrs = $reflector->getAttributes(Controller::class);
        if (!empty($controllerAttrs) && str_starts_with($className, 'App')) {
            $baseController = $this->get(BaseController::class);
            $method = new ReflectionMethod(BaseController::class, 'initControllerAndRoute');
            $method->invoke($baseController, $instance);
        }

        return $instance;
    }
}
