<?php

namespace Framework\Bootstrap;

use Framework\Core\BaseController;
use Framework\Core\ErrorConsole;
use Framework\DI\Context;
use Framework\Exceptions\RouterException;
use Framework\Core\ConfigSettings;
use Framework\Http\RequestHelper;
use Framework\Utils\Redirect;

/**
 * Router principal de la aplicación
 */
class Router
{
    private const ALLOWED_MODULES_BEFORE_INSTALL = [
        'installer',
        //'notification'
    ];

    private RequestHelper $requestHelper;
    private ConfigSettings $configSettings;
    private Context $context;
    private ErrorConsole $errorConsole;
    private Redirect $redirect;

    private ?string $cachePath = null;
    private bool $testingMode = false;

    public function __construct(
        RequestHelper $requestHelper,
        ConfigSettings $configSettings,
        Context $context,
        ErrorConsole $errorConsole,
        Redirect $redirect
    ) {
        $this->requestHelper = $requestHelper;
        $this->configSettings = $configSettings;
        $this->context = $context;
        $this->errorConsole = $errorConsole;
        $this->redirect = $redirect;
    }

    /**
     * Permite configurar una ruta de caché manualmente (para tests)
     */
    public function setCachePath(string $path): void
    {
        $this->cachePath = $path;
    }

    /**
     * Activa o desactiva modo de pruebas (evita exit() y sesiones reales)
     */
    public function enableTestingMode(bool $enabled = true): void
    {
        $this->testingMode = $enabled;
    }

    /**
     * Inicializa la aplicación y ejecuta el controller correspondiente
     */
    public function initBalero(): void
    {
        $this->initializeSession();
        $this->initializeBasepath();

        if ($this->handleInstallationCheck()) {
            return;
        }

        $requestedPath = $this->requestHelper->getPath();
        $matchedControllers = $this->findMatchingControllers($requestedPath);

        if (empty($matchedControllers)) {
            throw new RouterException("No controller found for path: {$requestedPath}");
        }

        $lastException = null;
        foreach ($matchedControllers as $matchedController) {
            try {
                $this->executeController($matchedController);
                return;
            } catch (RouterException $e) {
                // Si es un error de "ruta no encontrada", intentamos con el siguiente controller
                if (str_contains($e->getMessage(), 'Route not found')) {
                    $lastException = $e;
                    continue;
                }
                // Si es cualquier otro error (ej: Unauthorized), lo lanzamos inmediatamente
                throw $e;
            }
        }

        if ($lastException) {
            throw $lastException;
        }

        throw new RouterException("No controller found for path: {$requestedPath}");
    }

    private function initializeSession(): void
    {
        if ($this->testingMode) {
            $_SESSION = $_SESSION ?? [];
            $_SESSION['lang'] = $_SESSION['lang'] ?? $this->configSettings->language ?? 'en';
            return;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $_SESSION['lang'] = $_SESSION['lang'] ?? $this->configSettings->language ?? 'en';
    }

    private function initializeBasepath(): void
    {
        $installed = $this->configSettings->installed ?? 'no';
        $currentBasepath = $this->configSettings->basepath;

        // Calcular el path base actual
        $calculatedPath = rtrim($this->configSettings->getFullBasepath(), '/') . '/';

        if ($installed === 'no') {
            // Durante la instalación
            if (empty($currentBasepath) || $currentBasepath !== $calculatedPath) {
                $this->configSettings->basepath = $calculatedPath;
            }
        } else {
            // Sistema ya instalado
            if (empty($currentBasepath)) {
                $this->configSettings->basepath = $calculatedPath;
            }
            // Si ya tiene valor → NO TOCAR NADA
        }
    }

    private function findMatchingControllers(string $requestedPath): array
    {
        $cacheFile = $this->cachePath ?? BASE_PATH . '/cache/controllers.cache.php';

        if (!file_exists($cacheFile)) {
            throw new RouterException(
                "Routes cache file not found: {$cacheFile}. Please regenerate the cache."
            );
        }

        return $this->findFromCache($cacheFile, $requestedPath);
    }

    private function findFromCache(string $cacheFile, string $requestedPath): array
    {
        $controllers = require $cacheFile;
        // Ordenar por longitud del path (más largos primero)
        usort($controllers, fn($a, $b) => strlen($b['path']) <=> strlen($a['path']));

        $requestedPath = rtrim($requestedPath, '/') ?: '/';
        $matched = [];

        foreach ($controllers as $controller) {
            $path = rtrim($controller['path'], '/') ?: '/';

            if ($requestedPath === $path || ($path !== '/' && str_starts_with($requestedPath, $path . '/'))) {
                $matched[] = $controller['class'];
            }
        }

        return $matched;
    }

    private function executeController(string $matchedController): void
    {
        try {
            $controllerInstance = $this->context->get($matchedController);
            $baseController = $this->context->get(BaseController::class);
            $baseController->initControllerAndRoute($controllerInstance);
        } catch (\Throwable $e) {
            throw new RouterException(
                "Error loading controller '$matchedController': " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    private function handleInstallationCheck(): bool
    {
        $installed = $this->configSettings->installed ?? 'no';
        $requestedPath = $this->requestHelper->getPath();
        $pathSegments = array_filter(explode('/', trim($requestedPath, '/')));
        $currentModule = !empty($pathSegments) ? strtolower($pathSegments[0]) : '';

        if ($installed === 'no' && !in_array($currentModule, self::ALLOWED_MODULES_BEFORE_INSTALL)) {
            $this->redirect->to('/installer');
            if (!$this->testingMode) {
                exit;
            }
            return true;
        }

        if ($installed === 'yes' && $currentModule === 'installer') {
            $this->redirect->to('/');
            if (!$this->testingMode) {
                exit;
            }
            return true;
        }

        return false;
    }
}