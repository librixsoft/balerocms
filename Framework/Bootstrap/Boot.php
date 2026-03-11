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
use Framework\Bootstrap\Router;   // ← FIX: import que faltaba y causaba el fatal error
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
            // ⚡ CRÍTICO: Crear instancia de EarlyErrorConsole
            $this->earlyErrorConsole = new EarlyErrorConsole();

            // ⚡ CRÍTICO: Registrar manejador de errores básico INMEDIATAMENTE
            $this->registerEarlyErrorHandler();

            // CRÍTICO: Cargar el caché de DTOs ANTES de registrar el autoloader
            $this->loadDTOCache();

            // Obtener todos los autoloaders actuales (probablemente Composer)
            $existingAutoloaders = spl_autoload_functions();

            // Desregistrar todos los autoloaders existentes
            foreach ($existingAutoloaders as $autoloader) {
                spl_autoload_unregister($autoloader);
            }

            // Registrar NUESTRO autoloader PRIMERO
            spl_autoload_register([$this, 'autoloadClass'], true, false);

            // Re-registrar los autoloaders existentes DESPUÉS del nuestro
            foreach ($existingAutoloaders as $autoloader) {
                spl_autoload_register($autoloader, true, false);
            }
        }
    }

    // ─────────────────────────────────────────────
    // Handlers de error tempranos
    // ─────────────────────────────────────────────

    /**
     * Registra un manejador de errores básico ANTES de inicializar el container.
     * Captura errores tempranos antes de que ErrorConsole completo esté disponible.
     */
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

    /**
     * Renderiza un error temprano (antes de que ErrorConsole completo esté disponible).
     */
    protected function renderEarlyError(Throwable $e): void
    {
        $this->earlyErrorConsole->render($e);
    }

    // ─────────────────────────────────────────────
    // Inicialización principal
    // ─────────────────────────────────────────────

    /**
     * Inicializa Boot: container, caché de DTOs y opcionalmente el Router.
     *
     * @throws ContainerInitializationException
     * @throws RouterInitializationException
     * @throws DTOCacheException
     */
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

    /**
     * Crea e inicializa el Context (DI container).
     * Sobreescribible en tests.
     *
     * @throws ContainerInitializationException
     */
    protected function createContext(): void
    {
        try {
            $this->context = new Context();

            // ⚡ UPGRADE: Reemplazar el handler temporal con el ErrorConsole completo
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

    /**
     * Inicializa y despacha el Router.
     * Sobreescribible en tests.
     *
     * @throws RouterInitializationException
     */
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

    /**
     * Carga el caché de DTOs mejorados.
     * Sirve tanto para la carga temprana (constructor) como para la carga tardía (init).
     * Es idempotente: si ya fue cargado, retorna sin hacer nada.
     *
     * @throws DTOCacheException si el archivo no existe
     */
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

    /**
     * Retorna la ruta del archivo de caché de DTOs.
     * Sobreescribible en subclases para tests.
     */
    protected function getDtoCachePath(): string
    {
        return BASE_PATH . '/cache/dtos.cache.php';
    }

    // ─────────────────────────────────────────────
    // Autoloader PSR-4 con soporte para DTOs mejorados
    // ─────────────────────────────────────────────

    /**
     * Autoload de clases PSR-4 con soporte para DTOs mejorados.
     *
     * @throws AutoloadException
     * @throws DTOCacheException
     */
    public function autoloadClass(string $class): void
    {
        if ($this->testingMode) {
            if (!class_exists($class, false)) {
                $namespace = substr($class, 0, strrpos($class, '\\'));
                $className = substr($class, strrpos($class, '\\') + 1);
                eval("namespace $namespace; class $className {}");
            }
            return;
        }

        // 1. Intentar cargar como DTO mejorado desde caché
        if ($this->loadEnhancedDTO($class)) {
            return;
        }

        // 2. Autoload PSR-4 normal (solo si NO es un DTO mejorado)
        $relativePath = str_replace('\\', '/', ltrim($class, '\\')) . '.php';
        $file = BASE_PATH . '/' . $relativePath;

        // Bloquear carga directa de App/DTO/ si el DTO está marcado como mejorado
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

    /**
     * Verifica si una clase está registrada como DTO mejorado.
     */
    private function isEnhancedDTO(string $class): bool
    {
        return in_array($class, $this->enhancedDTOs, true);
    }

    /**
     * Intenta cargar un DTO mejorado desde el directorio de caché.
     *
     * @return bool true si se cargó (o ya estaba cargado), false si no es un DTO mejorado
     * @throws DTOCacheException si el archivo existe pero no define la clase esperada
     */
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

    /** Activa o desactiva el modo testing. */
    public function enableTestingMode(bool $enable = true): void
    {
        $this->testingMode = $enable;
    }

    /** Retorna true si el modo testing está activo. */
    public function isTestingMode(): bool
    {
        return $this->testingMode;
    }

    /** Retorna true si el caché de DTOs ya fue cargado. */
    public function isDtoCacheLoaded(): bool
    {
        return $this->dtoCacheLoaded;
    }

    /**
     * Inyecta la lista de DTOs mejorados directamente.
     * Usar solo en tests para evitar depender del archivo de caché.
     */
    public function setEnhancedDTOs(array $dtos): void
    {
        $this->enhancedDTOs  = $dtos;
        $this->dtoCacheLoaded = true;
    }

    /**
     * Expone getDtoCachePath() públicamente para inspección en tests.
     */
    public function getTestDtoCachePath(): string
    {
        return $this->getDtoCachePath();
    }

    /**
     * Ejecuta loadDTOCache() externamente (para tests que necesitan probar la carga directa).
     *
     * @throws DTOCacheException
     */
    public function callLoadDTOCacheEarly(): void
    {
        $this->loadDTOCache();
    }

    /**
     * Alias de callLoadDTOCacheEarly() para tests que prueban la carga tardía.
     *
     * @throws DTOCacheException
     */
    public function callLoadDTOCache(): void
    {
        $this->loadDTOCache();
    }
}