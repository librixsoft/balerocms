<?php

/**
 * Balero CMS
 * Context - Contenedor de dependencias global
 *
 * Proporciona acceso global a servicios registrados en el contenedor,
 * especialmente útil en clases que no pasan por DI directamente.
 *
 * @author Anibal Gomez
 * @license GNU General Public License
 */

namespace Framework\DI;

use Framework\Config\ContextConfig;

class Context
{
    /**
     * Contenedor de dependencias principal de la aplicación.
     *
     * @var Container
     */
    private Container $container;

    /**
     * Constructor de Context.
     *
     * Inicializa el contenedor y registra todos los servicios globales
     * necesarios para la aplicación.
     */
    public function __construct()
    {
        // Instanciar el contenedor internamente
        $this->container = new Container();

        // Registrar servicios
        $this->registerServices();
    }

    /**
     * Registra todos los servicios en el contenedor.
     *
     * @return void
     */
    private function registerServices(): void
    {
        $contextConfig = new ContextConfig();
        $contextConfig->register($this->container);
    }

    /**
     * Obtiene un servicio desde el contenedor.
     *
     * @template T
     * @param class-string<T> $class
     * @return T
     */
    public function get(string $class): object
    {
        return $this->container->get($class);
    }
}