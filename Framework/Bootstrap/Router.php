<?php

namespace Framework\Bootstrap;

use Framework\DI\Container;
use Framework\Http\RequestHelper;
use Framework\Core\ConfigSettings;
use Framework\Static\Redirect;
use Framework\Core\Controller;

class Router
{
    private const DEFAULT_MODULE = 'Block';
    private const PARAM_MODULE = 'module';

    private RequestHelper $requestHelper;
    private ConfigSettings $configSettings;

    private Container $container;

    public function __construct(RequestHelper $requestHelper, ConfigSettings $configSettings, Controller $controller, Container $container)
    {
        $this->requestHelper = $requestHelper;
        $this->configSettings = $configSettings;
        $this->controller = $controller;
        $this->container = $container; // guardamos container
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

        // Módulo por defecto
        $module = $currentModule ?: self::DEFAULT_MODULE;

        // Cargar controller usando DI
        $this->loadController($module);
    }

    public function loadController(string $module): void
    {
        $controllerClass = "Modules\\{$module}\\Controllers\\{$module}Controller";

        if (!class_exists($controllerClass)) {
            throw new \Framework\Exceptions\RouterException("Controller class not found: $controllerClass");
        }

        try {
            // Crear instancia usando el Container directamente
            $moduleController = $this->container->get($controllerClass);

            // Inicializar controller y route
            $this->controller->initControllerAndRoute($moduleController);

        } catch (\Throwable $e) {
            throw new \Framework\Exceptions\RouterException(
                "Error loading controller '$controllerClass': " . $e->getMessage(),
                0,
                $e
            );
        }
    }



}
