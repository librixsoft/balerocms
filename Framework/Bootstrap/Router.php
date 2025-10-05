<?php

namespace Framework\Bootstrap;

use Framework\Core\BaseController;
use Framework\DI\Container;
use Framework\Exceptions\RouterException;
use Framework\Http\RequestHelper;
use Framework\Core\ConfigSettings;

class Router
{

    private RequestHelper $requestHelper;
    private ConfigSettings $configSettings;
    private Container $container;

    /**
     * Router constructor.
     *
     * @param RequestHelper $requestHelper Helper for accessing HTTP request information
     * @param ConfigSettings $configSettings Global application configuration
     * @param Container $container Dependency injection container
     */
    public function __construct(RequestHelper $requestHelper, ConfigSettings $configSettings, Container $container)
    {
        $this->requestHelper = $requestHelper;
        $this->configSettings = $configSettings;
        $this->container = $container;
    }

    /**
     * Initializes the application and resolves the controller matching the requested path.
     *
     * - Starts the session if not already active.
     * - Sets the session language.
     * - Attempts to load controllers from cache.
     * - If cache does not exist, scans the controllers directory.
     * - Finds the controller that matches the requested URL.
     * - Instantiates the controller using the dependency container.
     *
     * @throws RouterException If no controller is found for the requested path or instantiation fails.
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

            $matchedControllerEntry = array_filter(
                $controllers,
                fn($controller) => str_starts_with($requestedPath, $controller['url'])
            );

            $matchedControllerEntry = array_shift($matchedControllerEntry);

            if (!$matchedControllerEntry) {
                throw new RouterException("No controller found for path: {$requestedPath}");
            }

            $matchedController = $matchedControllerEntry['class'];
        } else {
            if (!file_exists($cacheFile)) {
                echo '<div style="width:100%;padding:3px 0;background-color:rgba(255,165,0,0.7);color:white;font-weight:bold;text-align:center;font-size:12px;position:fixed;top:0;left:0;z-index:9999;margin:0;">Routes cache file does not exist: ' . $cacheFile . '</div>';
            }

            $controllers = $this->getControllersFromNamespace(
                'App\\Controllers',
                LOCAL_DIR . '/App/Controllers'
            );

            $baseController = $this->container->get(BaseController::class);
            $matchedController = null;

            // Mantener foreach aquí como estaba
            foreach ($controllers as $className) {
                $pathUrl = $baseController->getControllerPathUrl($className);
                if (str_starts_with($requestedPath, $pathUrl)) {
                    $matchedController = $className;
                    break;
                }
            }
        }

        // Instantiate the controller
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
     * If cache fails scans a directory of controllers and returns all classes within the given namespace.
     *
     * @param string $namespace Base namespace to search for classes
     * @param string $path Physical directory path containing the controllers
     * @return string[] Array of fully-qualified class names found
     */
    private function getControllersFromNamespace(string $namespace, string $path): array
    {
        $controllers = [];
        $files = scandir($path);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $fullPath = $path . DIRECTORY_SEPARATOR . $file;

            if (is_dir($fullPath)) {
                // Recursive scan for subfolders (e.g., ApiControllers, AdminControllers, etc.)
                $controllers = array_merge(
                    $controllers,
                    $this->getControllersFromNamespace($namespace . '\\' . $file, $fullPath)
                );
            } elseif (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $className = $namespace . '\\' . pathinfo($file, PATHINFO_FILENAME);

                // Check that the class exists (PSR-4 autoload will load it)
                if (class_exists($className)) {
                    $controllers[] = $className;
                }
            }
        }

        return $controllers;
    }
}
