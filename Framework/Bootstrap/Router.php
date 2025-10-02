<?php

namespace Framework\Bootstrap;

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

    private Controller $controller; // <-- aquí guardamos la instancia de Controller


    public function __construct(RequestHelper $requestHelper, ConfigSettings $configSettings,         Controller $controller   // <-- inyectamos Controller
    )
    {
        $this->requestHelper = $requestHelper;
        $this->configSettings = $configSettings;
        $this->controller = $controller; // <-- asignamos

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
        $allowedModules = ['installer', 'notification', 'test']; // módulos permitidos antes de instalar

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
            throw new \Framework\Exceptions\RouterException("Controller class not found: $controllerClass");
        }

        try {
            $moduleController = call_user_func($controllerResolver, $controllerClass);

            // 1️⃣ Llamamos primero a initControllerAndInject pasándole el ModuleController
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
