<?php

namespace Framework\Bootstrap;

use Framework\Core\ErrorConsole;
use Framework\Core\EarlyErrorConsole;
use Framework\Exceptions\BootException;
use Framework\Exceptions\AutoloadException;
use Framework\Exceptions\DTOCacheException;
use Framework\Exceptions\RouterInitializationException;
use Framework\Exceptions\ContainerInitializationException;
use Framework\DI\Context;
use Framework\Bootstrap\Router;
use Throwable;

class Boot
{
    private ?EarlyErrorConsole $earlyErrorConsole = null;
    private ?ErrorConsole $errorConsole = null;
    private ?Context $context = null;
    private bool $testingMode = false;
    private array $enhancedDTOs = [];
    private bool $dtoCacheLoaded = false;

    public function __construct(bool $testingMode = false)
    {
        $this->testingMode = $testingMode;

        if (!$this->testingMode) {
            $this->earlyErrorConsole = new EarlyErrorConsole();
            $this->registerEarlyErrorHandler();
            $this->loadDTOCache();

            $existingAutoloaders = spl_autoload_functions();
            foreach ($existingAutoloaders as $autoloader) {
                spl_autoload_unregister($autoloader);
            }

            spl_autoload_register([$this, 'autoloadClass'], true, false);

            foreach ($existingAutoloaders as $autoloader) {
                spl_autoload_register($autoloader, true, false);
            }
        }
    }

    // ─────────────────────────────────────────────
    // Handlers de error tempranos
    // ─────────────────────────────────────────────

    protected function registerEarlyErrorHandler(): void
    {
        if (!ob_get_level()) {
            ob_start();
        }

        ini_set('display_errors', '1');
        error_reporting(E_ALL);

        set_exception_handler(function (Throwable $e): void {
            $this->renderEarlyError($e);
        });

        set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
            $this->renderEarlyError(new \ErrorException($errstr, 0, $errno, $errfile, $errline));
            return true;
        });

        register_shutdown_function(function (): void {
            $error = error_get_last();
            if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                $this->renderEarlyError(
                    new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line'])
                );
            }
        });
    }

    protected function renderEarlyError(Throwable $e): void
    {
        $this->earlyErrorConsole->render($e);
    }

    // ─────────────────────────────────────────────
    // Inicialización principal
    // ─────────────────────────────────────────────

    public function init(bool $loadRouter = true): void
    {
        if ($this->testingMode) {
            return;
        }

        $this->createContext();
        $this->loadDTOCache();

        if ($loadRouter) {
            $this->dispatchRouter();
        }
    }

    protected function createContext(): void
    {
        try {
            $this->context = new Context();
            $this->errorConsole = $this->context->get(ErrorConsole::class);
            $this->errorConsole->register();
        } catch (Throwable $e) {
            throw new ContainerInitializationException(
                'Failed to initialize container context or ErrorConsole: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    protected function dispatchRouter(): void
    {
        try {
            $router = $this->context->get(Router::class);
            $router->initBalero();
        } catch (Throwable $e) {
            throw new RouterInitializationException(
                'Failed to initialize router: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    // ─────────────────────────────────────────────
    // Caché de DTOs
    // ─────────────────────────────────────────────

    private function loadDTOCache(): void
    {
        if ($this->dtoCacheLoaded || $this->testingMode) {
            return;
        }

        $dtoCacheFile = $this->getDtoCachePath();

        if (!file_exists($dtoCacheFile)) {
            throw new DTOCacheException(
                "DTOs cache file not found at: $dtoCacheFile. Please run the cache generation script."
            );
        }

        $this->enhancedDTOs = require $dtoCacheFile;
        $this->dtoCacheLoaded = true;
    }

    protected function getDtoCachePath(): string
    {
        return BASE_PATH . '/cache/dtos.cache.php';
    }

    // ─────────────────────────────────────────────
    // Autoloader PSR-4 con soporte para DTOs mejorados
    // ─────────────────────────────────────────────

    private function shouldSkipAutoload(string $class): bool
    {
        if ($this->testingMode) {
            if (!class_exists($class, false)) {
                $namespace = substr($class, 0, strrpos($class, '\\'));
                $className = substr($class, strrpos($class, '\\') + 1);
                eval("namespace $namespace; class $className {}");
            }
            return true;
        }

        // ── GUARD: si la clase ya está en memoria no hacer nada ──────────────
        // Evita "Cannot redeclare class" cuando Composer y nuestro autoloader
        // resuelven el mismo archivo desde rutas físicas distintas
        // (symlinks, paths con/sin trailing slash, etc.)
        return class_exists($class, false)
            || interface_exists($class, false)
            || trait_exists($class, false);
    }

    public function autoloadClass(string $class): void
    {
        if ($this->shouldSkipAutoload($class)) {
            return;
        }

        // 1. Intentar cargar como DTO mejorado desde caché
        if ($this->loadEnhancedDTO($class)) {
            return;
        }

        // 2. Autoload PSR-4 normal
        $relativePath = str_replace('\\', '/', ltrim($class, '\\')) . '.php';
        $file = BASE_PATH . '/' . $relativePath;

        if ($this->isEnhancedDTO($class) && strpos($file, '/App/DTO/') !== false) {
            throw new DTOCacheException(
                "DTO <code>$class</code> is marked as enhanced but cache file not found.<br>" .
                'Expected cache at: ' . BASE_PATH . '/cache/dtos/' . basename($relativePath) . '<br>' .
                'Run cache generation script to create enhanced DTOs.'
            );
        }

        if (file_exists($file)) {
            require_once $file;
            return;
        }

        throw new AutoloadException(
            "Error loading class <code>$class</code><br>Expected: <code>$relativePath</code>"
        );
    }

    private function isEnhancedDTO(string $class): bool
    {
        return in_array($class, $this->enhancedDTOs, true);
    }

    private function loadEnhancedDTO(string $class): bool
    {
        if (!$this->isEnhancedDTO($class)) {
            return false;
        }

        if (class_exists($class, false)) {
            return true;
        }

        $shortClassName = substr($class, strrpos($class, '\\') + 1);
        $enhancedFile   = BASE_PATH . '/cache/dtos/' . $shortClassName . '.php';

        if (!file_exists($enhancedFile)) {
            return false;
        }

        require_once $enhancedFile;

        if (!class_exists($class, false)) {
            throw new DTOCacheException("Failed to load enhanced DTO: $class from $enhancedFile");
        }

        return true;
    }

    // ─────────────────────────────────────────────
    // API pública de soporte / testing
    // ─────────────────────────────────────────────

    public function enableTestingMode(bool $enable = true): void
    {
        $this->testingMode = $enable;
    }

    public function isTestingMode(): bool
    {
        return $this->testingMode;
    }

    public function isDtoCacheLoaded(): bool
    {
        return $this->dtoCacheLoaded;
    }

    public function setEnhancedDTOs(array $dtos): void
    {
        $this->enhancedDTOs   = $dtos;
        $this->dtoCacheLoaded = true;
    }

    public function getTestDtoCachePath(): string
    {
        return $this->getDtoCachePath();
    }

    public function callLoadDTOCacheEarly(): void
    {
        $this->loadDTOCache();
    }

    public function callLoadDTOCache(): void
    {
        $this->loadDTOCache();
    }
}