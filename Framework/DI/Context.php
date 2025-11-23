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

use Framework\Config\ViewConfig;
use Framework\Config\SetupConfig;
use Framework\Core\ConfigSettings;
use Framework\Core\View;
use Framework\Core\ErrorConsole;
use Framework\Utils\Redirect;

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
    // TODO: Separate this to a config class
    private function registerServices(): void
    {
        $container = $this->container;

        $container->singleton(SetupConfig::class, function() {
            return new SetupConfig(BASE_PATH . '/resources/config/balero.config.json');
        });

        $container->singleton(ConfigSettings::class, function() use ($container) {
            return new ConfigSettings($container->get(SetupConfig::class));
        });

        $config = $container->get(ConfigSettings::class);
        $config->getHandler();

        $container->singleton(ViewConfig::class, function() {
            return new ViewConfig(
                BASE_PATH . '/resources/views',
                BASE_PATH . '/resources/lang',
                ['html']
            );
        });

        $view = $container->get(View::class);
        $container->set(View::class, $view);

        $errorConsole = new ErrorConsole($config, $this);
        $container->set(ErrorConsole::class, $errorConsole);

        $redirect = new Redirect($config);
        $container->set(Redirect::class, $redirect);
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