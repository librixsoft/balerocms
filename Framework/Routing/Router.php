<?php

namespace Framework\Routing;

use Framework\Http\RequestHelper;
use Framework\Core\ConfigSettings;
use Framework\Config\Context;
use Framework\Static\Redirect;
use Framework\Exceptions\RouterException;
use Throwable;

class Router
{
    private const DEFAULT_MODULE = 'Block';
    private const PARAM_MODULE = 'module';

    /**
     * Inicializa la app.
     *
     * @param RequestHelper $request
     * @param ConfigSettings $configSettings
     * @param Context $context
     * @param callable $controllerResolver Callback que recibe nombre de clase y devuelve instancia
     */
    public function initBalero(RequestHelper $request, ConfigSettings $configSettings, Context $context, callable $controllerResolver): void
    {
        // Sesión
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!isset($_SESSION['lang'])) {
            $_SESSION['lang'] = $configSettings->language ?? 'en';
        }

        if (!isset($configSettings->basepath) || $configSettings->basepath === '') {
            $configSettings->basepath = rtrim($configSettings->getFullBasepath(), '/') . '/';
        }

        $currentModule = $request->get(self::PARAM_MODULE);

        if ($currentModule === 'notification') return;

        $installed = $configSettings->installed;

        if ($installed === "no" && $currentModule !== 'installer') {
            Redirect::to('/installer');
            exit;
        }

        if ($installed === "yes" && $currentModule === 'installer') {
            Redirect::to('/');
            exit;
        }

        $module = $currentModule ?: self::DEFAULT_MODULE;
        $this->loadController($module, $context, $controllerResolver);
    }

    /**
     * Carga un controller usando el callback de Boot
     */
    public function loadController(string $module, Context $context, callable $controllerResolver): void
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
