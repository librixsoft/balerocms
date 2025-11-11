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
 */
class Router
{
    private const ALLOWED_MODULES_BEFORE_INSTALL = [
        'installer',
        //'notification'
    ];

    private RequestHelper $requestHelper;
    private ConfigSettings $configSettings;
    private Container $container;
    private ErrorConsole $errorConsole;
    private Redirect $redirect;

    private ?string $cachePath = null;
    private bool $testingMode = false;

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
        $matchedController = $this->findMatchingController($requestedPath);

        if (!$matchedController) {
            throw new RouterException("No controller found for path: {$requestedPath}");
        }

        $this->executeController($matchedController);
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
        if (empty($this->configSettings->basepath)) {
            $this->configSettings->basepath = rtrim(
                    $this->configSettings->getFullBasepath(),
                    '/'
                ) . '/';
        }
    }

    private function findMatchingController(string $requestedPath): ?string
    {
        $cacheFile = $this->cachePath ??
            (defined('BASE_PATH') ? BASE_PATH . '/cache/controllers.cache.php' : __DIR__ . '/../../cache/controllers.cache.php');

        if (file_exists($cacheFile)) {
            return $this->findFromCache($cacheFile, $requestedPath);
        }

        $this->errorConsole->warning("Routes cache file does not exist: $cacheFile");
        return null;
    }

    private function findFromCache(string $cacheFile, string $requestedPath): ?string
    {
        $controllers = require $cacheFile;
        usort($controllers, fn($a, $b) => strlen($b['path']) <=> strlen($a['path']));

        $requestedPath = rtrim($requestedPath, '/') ?: '/';

        foreach ($controllers as $controller) {
            $path = rtrim($controller['path'], '/') ?: '/';

            if ($requestedPath === $path || ($path !== '/' && str_starts_with($requestedPath, $path . '/'))) {
                return $controller['class'];
            }
        }

        return null;
    }

    private function executeController(string $matchedController): void
    {
        try {
            $controllerInstance = $this->container->get($matchedController);
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
