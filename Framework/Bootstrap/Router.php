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

        $currentModule = $this->requestHelper->get(self::PARAM_MODULE);

        // Comprobar instalación
        $installed = $this->configSettings->installed;
        $allowedModules = ['installer', 'notification', 'test']; // módulos permitidos antes de instalar

        if ($installed === "no" && !in_array($currentModule, $allowedModules)) {
            Redirect::to('/installer');
            exit;
        }

        if ($installed === "yes" && $currentModule === 'installer') {
            Redirect::to('/');
            exit;
        }

        // Módulo por defecto si no se pasa ninguno
        $module = $currentModule ?: self::DEFAULT_MODULE;

        // Construir namespace del controlador
        $controllerClass = "Modules\\{$module}\\Controllers\\{$module}Controller";

        // Si no existe el controlador, lanzar excepción
        if (!class_exists($controllerClass)) {
            throw new \Framework\Exceptions\RouterException("Controller class not found: $controllerClass");
        }

        try {
            // Resolver controlador desde el contenedor con inyección de dependencias
            //$moduleController = $this->container->get($controllerClass);
            //$moduleController->something()
            $this->container->get($controllerClass);

        } catch (\Throwable $e) {
            throw new \Framework\Exceptions\RouterException(
                "Error loading controller '$controllerClass': " . $e->getMessage(),
                0,
                $e
            );
        }
    }

}
