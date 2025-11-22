<?php

namespace Framework\Bootstrap;

use Framework\Core\ErrorConsole;
use Framework\DI\Container;
use Framework\Exceptions\BootException;
use Framework\Exceptions\AutoloadException;
use Framework\Exceptions\DTOCacheException;
use Framework\Exceptions\RouterInitializationException;
use Framework\Exceptions\ContainerInitializationException;
use Framework\DI\Context;
use Throwable;

class Boot
{
    private ErrorConsole $errorConsole;
    private ?Container $container = null;
    private bool $testingMode = false;
    private array $enhancedDTOs = [];
    private bool $dtoCacheLoaded = false;

    public function __construct(bool $testingMode = false)
    {
        $this->testingMode = $testingMode;

        if (!$this->testingMode) {
            // CRÍTICO: Cargar el caché de DTOs ANTES de registrar el autoloader
            $this->loadDTOCacheEarly();

            // Obtener todos los autoloaders actuales (probablemente Composer)
            $existingAutoloaders = spl_autoload_functions();

            // Desregistrar todos los autoloaders existentes
            foreach ($existingAutoloaders as $autoloader) {
                spl_autoload_unregister($autoloader);
            }

            // Registrar NUESTRO autoloader PRIMERO
            spl_autoload_register([$this, "autoloadClass"], true, false);

            // Re-registrar los autoloaders existentes DESPUÉS del nuestro
            foreach ($existingAutoloaders as $autoloader) {
                spl_autoload_register($autoloader, true, false);
            }

            $this->container = new Container();

            if ($this->container === null) {
                throw new BootException("Container initialization failed unexpectedly.");
            }
        }
    }

    /**
     * Inicializa Boot
     *
     * @param bool $loadRouter Indica si se debe inicializar Router
     * @throws BootException
     */
    public function init(bool $loadRouter = true): void
    {
        // En modo testing, crear un container mock solo si se necesita
        if ($this->testingMode) {
            return; // En testing mode no hacemos nada
        }

        try {
            if (!$this->testingMode) {
                new Context($this->container);
            }

            $this->errorConsole = $this->container->get(ErrorConsole::class);
            $this->errorConsole->register();

        } catch (Throwable $e) {
            throw new ContainerInitializationException("Failed to initialize container context or ErrorConsole: " . $e->getMessage(), 0, $e);
        }

        // Cargar caché de DTOs mejorados (para logging, ya está cargado desde constructor)
        $this->loadDTOCache();

        if ($loadRouter) {
            try {
                $router = $this->container->get(Router::class);
                $router->initBalero();
            } catch (Throwable $e) {
                throw new RouterInitializationException("Failed to initialize router: " . $e->getMessage(), 0, $e);
            }
        }
    }

    /**
     * Carga el caché de DTOs mejorados ANTES de inicializar el container
     * (no puede usar ErrorConsole porque no está disponible aún)
     */
    private function loadDTOCacheEarly(): void
    {
        if ($this->dtoCacheLoaded || $this->testingMode) {
            return;
        }

        $dtoCacheFile = BASE_PATH . '/cache/dtos.cache.php';

        if (!file_exists($dtoCacheFile)) {
            throw new DTOCacheException("DTOs cache file not found at: $dtoCacheFile. Please run the cache generation script.");
        }

        $this->enhancedDTOs = require $dtoCacheFile;
        $this->dtoCacheLoaded = true;
    }

    /**
     * Carga el caché de DTOs mejorados (versión para usar en init)
     */
    private function loadDTOCache(): void
    {
        if ($this->dtoCacheLoaded || $this->testingMode) {
            return;
        }

        $dtoCacheFile = BASE_PATH . '/cache/dtos.cache.php';

        if (!file_exists($dtoCacheFile)) {
            throw new DTOCacheException("DTOs cache file not found at: $dtoCacheFile. Please run the cache generation script.");
        }

        $this->enhancedDTOs = require $dtoCacheFile;
        $this->dtoCacheLoaded = true;
    }

    /**
     * Activa/desactiva modo testing
     */
    public function enableTestingMode(bool $enable = true): void
    {
        $this->testingMode = $enable;
    }

    public function isTestingMode(): bool
    {
        return $this->testingMode;
    }

    /**
     * Autoload de clases PSR-4 con soporte para DTOs mejorados
     */
    public function autoloadClass(string $class): void
    {
        if ($this->testingMode) {
            // Crear clase vacía en memoria para tests
            if (!class_exists($class, false)) {
                $namespace = substr($class, 0, strrpos($class, '\\'));
                $className = substr($class, strrpos($class, '\\') + 1);
                eval("namespace $namespace; class $className {}");
            }
            return;
        }

        // 1. PRIMERO: Verificar si es un DTO mejorado y cargarlo desde caché
        if ($this->loadEnhancedDTO($class)) {
            return; // Salir inmediatamente si se cargó desde caché
        }

        // 2. Autoload PSR-4 normal (solo si NO es un DTO mejorado)
        $baseDirs = [BASE_PATH . '/'];
        $relativeClass = ltrim($class, '\\');
        $relativePath = str_replace('\\', '/', $relativeClass) . '.php';

        foreach ($baseDirs as $baseDir) {
            $file = $baseDir . $relativePath;

            // BLOQUEAR: NO cargar archivos de App/DTO si es un DTO mejorado
            if ($this->isEnhancedDTO($class) && strpos($file, '/App/DTO/') !== false) {
                throw new DTOCacheException(
                    "DTO <code>$class</code> is marked as enhanced but cache file not found.<br>" .
                    "Expected cache at: " . BASE_PATH . '/cache/dtos/' . basename($class) . '.php<br>' .
                    "Run cache generation script to create enhanced DTOs."
                );
            }

            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }

        throw new AutoloadException("Error loading class <code>$class</code><br>Expected: <code>$relativePath</code>");
    }

    /**
     * Verifica si una clase es un DTO mejorado
     */
    private function isEnhancedDTO(string $class): bool
    {
        return in_array($class, $this->enhancedDTOs, true);
    }

    /**
     * Intenta cargar un DTO mejorado desde el caché
     *
     * @return bool True si se cargó un DTO mejorado, false si no
     */
    private function loadEnhancedDTO(string $class): bool
    {
        // Si no está en la lista de DTOs mejorados, retornar false
        if (!$this->isEnhancedDTO($class)) {
            return false;
        }

        // Si la clase ya fue cargada, no hacer nada más
        if (class_exists($class, false)) {
            return true;
        }

        // Extraer el nombre corto de la clase
        $parts = explode('\\', $class);
        $shortClassName = end($parts);

        $enhancedFile = BASE_PATH . '/cache/dtos/' . $shortClassName . '.php';

        if (!file_exists($enhancedFile)) {
            return false;
        }

        // Cargar la versión mejorada del caché
        require_once $enhancedFile;

        // Verificar que la clase se cargó correctamente
        if (!class_exists($class, false)) {
            throw new DTOCacheException("Failed to load enhanced DTO: $class from $enhancedFile");
        }

        return true;
    }
}