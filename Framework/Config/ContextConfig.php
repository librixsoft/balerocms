<?php

/**
 * Balero CMS
 * ContextConfig - Configuración de servicios del contexto
 *
 * Centraliza el registro de todos los servicios del contenedor de dependencias.
 *
 * @author Anibal Gomez
 * @license GNU General Public License
 */

namespace Framework\Config;

use Framework\DI\Container;
use Framework\Core\ConfigSettings;
use Framework\Core\View;
use Framework\Core\ErrorConsole;
use Framework\Utils\Redirect;

class ContextConfig
{
    /**
     * Registra los servicios en el contenedor.
     *
     * @param Container $container El contenedor de dependencias
     * @return void
     */
    public function register(Container $container): void
    {
        // Registrar configuración principal (SetupConfig)
        $container->singleton(SetupConfig::class, function() {
            return new SetupConfig(BASE_PATH . '/resources/config/balero.config.json');
        });

        // Registrar ConfigSettings (depende de SetupConfig)
        $container->singleton(ConfigSettings::class, function() use ($container) {
            return new ConfigSettings($container->get(SetupConfig::class));
        });

        // Inicializar el handler de configuración
        $config = $container->get(ConfigSettings::class);
        $config->getHandler();

        // Registrar configuración de vistas (ViewConfig)
        $container->singleton(ViewConfig::class, function() {
            return new ViewConfig(
                BASE_PATH . '/resources/views',
                BASE_PATH . '/resources/lang',
                ['html']
            );
        });

        // Registrar el servicio de vistas (View)
        $view = $container->get(View::class);
        $container->set(View::class, $view);

        // Registrar ErrorConsole (recibe Container, no Context)
        // Esto evita dependencia circular y permite resolver dinámicamente
        $container->singleton(ErrorConsole::class, function() use ($container) {
            $config = $container->get(ConfigSettings::class);
            return new ErrorConsole($config, $container);
        });

        // Registrar Redirect
        $container->singleton(Redirect::class, function() use ($container) {
            $config = $container->get(ConfigSettings::class);
            return new Redirect($config);
        });
    }
}