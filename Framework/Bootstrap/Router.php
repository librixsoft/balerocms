<?php

namespace Framework\Bootstrap;

use Framework\Core\BaseController;
use Framework\Core\ErrorConsole;
use Framework\DI\Container;
use Framework\Exceptions\RouterException;
use Framework\Core\ConfigSettings;
use Framework\Http\RequestHelper;
use Framework\Utils\Redirect;

/**
 * Router principal de la aplicación
 *
 * Responsabilidades:
 * - Inicializar sesión y configuración base
 * - Verificar estado de instalación y redireccionar si es necesario
 * - Encontrar el controller que maneja la ruta solicitada
 * - Instanciar el controller vía Container
 * - Delegar a BaseController para routing interno y ejecución
 */
class Router
{
    private const ALLOWED_MODULES_BEFORE_INSTALL = ['installer', 'notification'];

    private RequestHelper $requestHelper;
    private ConfigSettings $configSettings;
    private Container $container;
    private ErrorConsole $errorConsole;
    private Redirect $redirect;

    public function __construct(
        RequestHelper $requestHelper,
        ConfigSettings $configSettings,
        Container $container,
        ErrorConsole $errorConsole,
        Redirect $redirect
    ) {
        $this->requestHelper = $requestHelper;
        $this->configSettings = $configSettings;
        $this->container = $container;
        $this->errorConsole = $errorConsole;
        $this->redirect = $redirect;
    }

    /**
     * Inicializa la aplicación y ejecuta el controller/método correspondiente
     *
     * Flujo:
     * 1. Inicia sesión y configura basepath
     * 2. Verifica estado de instalación y redirecciona si es necesario
     * 3. Encuentra el controller que maneja la ruta
     * 4. Instancia el controller (Container)
     * 5. Ejecuta el routing interno (BaseController)
     *
     * @throws RouterException Si no se encuentra controller o falla la instanciación
     */
    public function initBalero(): void
    {
        $this->initializeSession();
        $this->initializeBasepath();

        // Verificar instalación y redireccionar si es necesario
        if ($this->handleInstallationCheck()) {
            return; // Se ejecutó una redirección
        }

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
        $cacheFile = BASE_PATH . '/cache/controllers.cache.php';

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

        // Ordenar rutas por longitud descendente (más específicas primero)
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
            BASE_PATH . '/App/Controllers'
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

    /**
     * Verifica el estado de instalación y redirecciona si es necesario
     *
     * Reglas:
     * - Si NO está instalada y se intenta acceder a módulos no permitidos -> redirige a /installer
     * - Si YA está instalada y se intenta acceder a /installer -> redirige a /
     *
     * @return bool True si se ejecutó una redirección, false si se puede continuar
     */
    private function handleInstallationCheck(): bool
    {
        $installed = $this->configSettings->installed ?? 'no';
        $requestedPath = $this->requestHelper->getPath();

        // Extraer el módulo/ruta base (primer segmento)
        $pathSegments = array_filter(explode('/', trim($requestedPath, '/')));
        $currentModule = !empty($pathSegments) ? strtolower($pathSegments[0]) : '';

        // App NO instalada: redirigir a installer si se intenta acceder a rutas no permitidas
        if ($installed === 'no' && !in_array($currentModule, self::ALLOWED_MODULES_BEFORE_INSTALL)) {
            $this->redirect->to('/installer');
            exit;
        }

        // App instalada: redirigir a home si se intenta acceder al installer
        if ($installed === 'yes' && $currentModule === 'installer') {
            $this->redirect->to('/');
            exit;
        }

        return false; // No se ejecutó redirección, continuar con el flujo normal
    }
}