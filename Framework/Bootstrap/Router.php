<?php

namespace Framework\Bootstrap;

use Framework\Core\BaseController;
use Framework\Core\ErrorConsole;
use Framework\DI\Container;
use Framework\Exceptions\RouterException;
use Framework\Http\RequestHelper;
use Framework\Core\ConfigSettings;

/**
 * Router principal de la aplicación
 *
 * Responsabilidades:
 * - Inicializar sesión y configuración base
 * - Encontrar el controller que maneja la ruta solicitada
 * - Instanciar el controller vía Container
 * - Delegar a BaseController para routing interno y ejecución
 */
class Router
{
    private RequestHelper $requestHelper;
    private ConfigSettings $configSettings;
    private Container $container;
    private ErrorConsole $errorConsole;

    public function __construct(
        RequestHelper $requestHelper,
        ConfigSettings $configSettings,
        Container $container,
        ErrorConsole $errorConsole
    ) {
        $this->requestHelper = $requestHelper;
        $this->configSettings = $configSettings;
        $this->container = $container;
        $this->errorConsole = $errorConsole;
    }

    /**
     * Inicializa la aplicación y ejecuta el controller/método correspondiente
     *
     * Flujo:
     * 1. Inicia sesión y configura basepath
     * 2. Encuentra el controller que maneja la ruta
     * 3. Instancia el controller (Container)
     * 4. Ejecuta el routing interno (BaseController)
     *
     * @throws RouterException Si no se encuentra controller o falla la instanciación
     */
    public function initBalero(): void
    {
        $this->initializeSession();
        $this->initializeBasepath();

        $requestedPath = $this->requestHelper->getPath();
        $matchedController = $this->findMatchingController($requestedPath);

        if (!$matchedController) {
            throw new RouterException("No controller found for path: {$requestedPath}");
        }

        $this->executeController($matchedController);
    }

    /**
     * Inicializa la sesión y el idioma
     */
    private function initializeSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $_SESSION['lang'] = $_SESSION['lang'] ?? $this->configSettings->language ?? 'en';
    }

    /**
     * Configura el basepath de la aplicación
     */
    private function initializeBasepath(): void
    {
        if (empty($this->configSettings->basepath)) {
            $this->configSettings->basepath = rtrim(
                    $this->configSettings->getFullBasepath(),
                    '/'
                ) . '/';
        }
    }

    /**
     * Encuentra el controller que coincide con la ruta solicitada
     *
     * Prioriza el cache, si no existe escanea directorios
     *
     * @param string $requestedPath Ruta solicitada (ej: /installer/)
     * @return string|null FQCN del controller o null si no se encuentra
     */
    private function findMatchingController(string $requestedPath): ?string
    {
        $cacheFile = LOCAL_DIR . '/cache/controllers.cache.php';

        if (file_exists($cacheFile)) {
            return $this->findFromCache($cacheFile, $requestedPath);
        }

        $this->errorConsole->warning("Routes cache file does not exist: $cacheFile");
        return $this->findByScan($requestedPath);
    }

    /**
     * Busca controller desde el archivo de cache
     *
     * @param string $cacheFile Ruta del archivo cache
     * @param string $requestedPath Ruta solicitada
     * @return string|null FQCN del controller encontrado
     */
    private function findFromCache(string $cacheFile, string $requestedPath): ?string
    {
        $controllers = require $cacheFile;

        // 🔧 Ordenar rutas por longitud descendente (más específicas primero)
        usort($controllers, fn($a, $b) => strlen($b['path']) <=> strlen($a['path']));

        // Normalizar paths (sin slash final excepto la raíz)
        $requestedPath = rtrim($requestedPath, '/') ?: '/';

        foreach ($controllers as $controller) {
            $path = rtrim($controller['path'], '/') ?: '/';

            if ($requestedPath === $path) {
                return $controller['class'];
            }

            if ($path !== '/' && str_starts_with($requestedPath, $path . '/')) {
                return $controller['class'];
            }
        }

        return null;
    }

    /**
     * Busca controller escaneando el directorio App/Controllers
     *
     * @param string $requestedPath Ruta solicitada
     * @return string|null FQCN del controller encontrado
     */
    private function findByScan(string $requestedPath): ?string
    {
        $controllers = $this->getControllersFromNamespace(
            'App\\Controllers',
            LOCAL_DIR . '/App/Controllers'
        );

        $baseController = $this->container->get(BaseController::class);

        foreach ($controllers as $className) {
            $pathUrl = $baseController->getControllerPathUrl($className);
            if (str_starts_with($requestedPath, $pathUrl)) {
                return $className;
            }
        }

        return null;
    }

    /**
     * Instancia y ejecuta el controller encontrado
     *
     * @param string $matchedController FQCN del controller
     * @throws RouterException Si falla la instanciación o ejecución
     */
    private function executeController(string $matchedController): void
    {
        try {
            // 1. Instanciar controller (Container resuelve dependencias)
            $controllerInstance = $this->container->get($matchedController);

            // 2. Ejecutar routing interno y método correspondiente
            $baseController = $this->container->get(BaseController::class);
            $baseController->initControllerAndRoute($controllerInstance);

        } catch (\Throwable $e) {
            throw new RouterException(
                "Error loading controller '$matchedController': " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Escanea directorio de controllers y retorna todas las clases encontradas
     *
     * Recursivo: escanea subdirectorios (ej: ApiControllers, AdminControllers)
     *
     * @param string $namespace Namespace base (ej: App\Controllers)
     * @param string $path Ruta física del directorio
     * @return string[] Array de FQCN de controllers encontrados
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
                // Escaneo recursivo de subdirectorios
                $controllers = array_merge(
                    $controllers,
                    $this->getControllersFromNamespace($namespace . '\\' . $file, $fullPath)
                );
            } elseif (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $className = $namespace . '\\' . pathinfo($file, PATHINFO_FILENAME);

                // Verificar que la clase existe (autoload PSR-4)
                if (class_exists($className)) {
                    $controllers[] = $className;
                }
            }
        }

        return $controllers;
    }
}