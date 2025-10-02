<?php

namespace Framework\Routing;

use Framework\Http\RequestHelper;
use Framework\Core\ConfigSettings;
use Framework\Static\Redirect;
use Framework\Exceptions\RouterException;
use Throwable;

class Router
{
    private const DEFAULT_MODULE = 'Block';
    private const PARAM_MODULE = 'module';

    private RequestHelper $requestHelper;
    private ConfigSettings $configSettings;
    
    public function __construct(RequestHelper $requestHelper, ConfigSettings $configSettings)
    {
        $this->requestHelper = $requestHelper;
        $this->configSettings = $configSettings;
    }


    /**
     * Inicializa la app.
     *
     * @param RequestHelper $requestHelper
     * @param ConfigSettings $configSettings
     * @param callable $controllerResolver Callback que recibe nombre de clase y devuelve instancia
     */
    public function initBalero(callable $controllerResolver): void
    {
        // Sesión
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!isset($_SESSION['lang'])) {
            $_SESSION['lang'] = $configSettings->language ?? 'en';
        }

        if (!isset($this->configSettings ->basepath) || $this->configSettings ->basepath === '') {
            $this->configSettings ->basepath = rtrim($this->configSettings ->getFullBasepath(), '/') . '/';
        }

        $currentModule = $this->requestHelper->get(self::PARAM_MODULE);

        $installed = $this->configSettings ->installed;
        $allowedModules = ['installer', 'notification']; // módulos permitidos antes de instalar

        if ($installed === "no" && !in_array($currentModule, $allowedModules)) {
            Redirect::to('/installer');
            exit;
        }

        if ($installed === "yes" && $currentModule === 'installer') {
            Redirect::to('/');
            exit;
        }

        $module = $currentModule ?: self::DEFAULT_MODULE;
        $this->loadController($module, $controllerResolver);
    }

    /**
     * Carga un controller usando el callback de Boot
     */
    public function loadController(string $module, callable $controllerResolver): void
    {
        $controllerClass = "Modules\\{$module}\\Controllers\\{$module}Controller";

        if (!class_exists($controllerClass)) {
            throw new RouterException("Controller class not found: $controllerClass");
        }

        try {
            $instance = call_user_func($controllerResolver, $controllerClass);

            if (method_exists($instance, 'initControllerAndInject')) {
                $instance->initControllerAndInject();
            }
        } catch (Throwable $e) {
            throw new RouterException(
                "Error loading controller '$controllerClass': " . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
