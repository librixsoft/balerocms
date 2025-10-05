<?php

namespace Framework\Bootstrap;

use Framework\Core\BaseController;
use Framework\DI\Container;
use Framework\Exceptions\RouterException;
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
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $_SESSION['lang'] = $_SESSION['lang'] ?? $this->configSettings->language ?? 'en';
        $this->configSettings->basepath = $this->configSettings->basepath ?: rtrim($this->configSettings->getFullBasepath(), '/') . '/';

        $requestedPath = $this->requestHelper->getPath();
        $cacheFile = LOCAL_DIR . '/cache/controllers.cache.php';

        if (file_exists($cacheFile)) {
            $controllers = require $cacheFile;

            $matchedController = null;
            foreach ($controllers as $controller) {
                if (str_starts_with($requestedPath, $controller['url'])) {
                    $matchedController = $controller['class'];
                    break;
                }
            }

            if (!$matchedController) {
                throw new RouterException("No controller found for path: {$requestedPath}");
            }
        } else {
            if (!file_exists($cacheFile)) echo '<div style="width:100%;padding:3px 0;background-color:rgba(255,165,0,0.7);color:white;font-weight:bold;text-align:center;font-size:12px;position:fixed;top:0;left:0;z-index:9999;margin:0;">Routes cache file does not exist: ' . $cacheFile . '</div>';
            $controllers = $this->getControllersFromNamespace(
                'App\\Controllers',
                LOCAL_DIR . '/App/Controllers'
            );

            $baseController = $this->container->get(BaseController::class);
            $matchedController = null;

            foreach ($controllers as $className) {
                $pathUrl = $baseController->getControllerPathUrl($className);
                if (str_starts_with($requestedPath, $pathUrl)) {
                    $matchedController = $className;
                    break;
                }
            }

            if (!$matchedController) {
                throw new RouterException("No controller found for path: {$requestedPath}");
            }
        }

        // Instanciar el controller
        try {
            $this->container->get($matchedController);
        } catch (\Throwable $e) {
            throw new RouterException(
                "Error loading controller '$matchedController': " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Escanea un directorio y devuelve las clases dentro de un namespace.
     */
    public function getControllersFromNamespace(string $namespace, string $path): array
    {
        $controllers = [];
        $files = scandir($path);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $fullPath = $path . DIRECTORY_SEPARATOR . $file;

            if (is_dir($fullPath)) {
                // Recursivo si hay subcarpetas (ej: ApiControllers, AdminControllers, etc.)
                $controllers = array_merge(
                    $controllers,
                    $this->getControllersFromNamespace($namespace . '\\' . $file, $fullPath)
                );
            } elseif (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $className = $namespace . '\\' . pathinfo($file, PATHINFO_FILENAME);

                // Verificar que la clase exista (autoload PSR-4 la cargará)
                if (class_exists($className)) {
                    $controllers[] = $className;
                }
            }
        }

        return $controllers;
    }


}
