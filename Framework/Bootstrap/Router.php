<?php

namespace Framework\Bootstrap;

use Framework\DI\Container;
use Framework\Http\RequestHelper;
use Framework\Core\ConfigSettings;
use Framework\Static\Redirect;

class Router
{
    private const DEFAULT_MODULE = 'Block';
    private const PARAM_MODULE = 'module';

    private RequestHelper $requestHelper;
    private ConfigSettings $configSettings;

    private Container $container;

    public function __construct(RequestHelper $requestHelper, ConfigSettings $configSettings, Container $container)
    {
        $this->requestHelper = $requestHelper;
        $this->configSettings = $configSettings;
        $this->container = $container;
    }


    /**
     * Inicializa la app.
     *
     * @param RequestHelper $requestHelper
     * @param ConfigSettings $configSettings
     * @param callable $controllerResolver Callback que recibe nombre de clase y devuelve instancia
     */
    public function initBalero(): void
    {
        // Iniciar sesión si no está activa
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Configurar idioma por defecto
        if (!isset($_SESSION['lang'])) {
            $_SESSION['lang'] = $this->configSettings->language ?? 'en';
        }

        // Asegurar basepath
        if (!isset($this->configSettings->basepath) || $this->configSettings->basepath === '') {
            $this->configSettings->basepath = rtrim($this->configSettings->getFullBasepath(), '/') . '/';
        }

        $requestedPath = $this->requestHelper->getPath(); // Ruta solicitada

        // Lista de controladores disponibles manualmente
        $controllers = [
            \App\Controllers\TestController::class,
            \App\Controllers\HomeController::class,
            // agregar aquí más controladores según necesites
        ];

        $found = false;
        foreach ($controllers as $controllerClass) {
            $reflector = new \ReflectionClass($controllerClass);
            $attrs = $reflector->getAttributes(\Framework\Attributes\Controller::class);

            if (empty($attrs)) continue;

            $pathUrl = rtrim($attrs[0]->newInstance()->pathUrl, '/');

            // Comparar si la ruta solicitada empieza con la ruta base del controlador
            if (str_starts_with($requestedPath, $pathUrl)) {
                try {
                    // Resolver con Container (inyección de dependencias)
                    $instance = $this->container->get($controllerClass);
                    $instance->initControllerAndRoute();
                    $found = true;
                    break;
                } catch (\Throwable $e) {
                    throw new \Framework\Exceptions\RouterException(
                        "Error loading controller '$controllerClass': " . $e->getMessage(),
                        0,
                        $e
                    );
                }
            }
        }

        if (!$found) {
            throw new \Framework\Exceptions\RouterException("No controller found for path: {$requestedPath}");
        }
    }

}
