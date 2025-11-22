<?php

namespace Framework\Bootstrap;

use Framework\Core\ErrorConsole;
use Framework\DI\Container;
use Framework\Exceptions\BootException;
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
            spl_autoload_register([$this, "autoloadClass"]);
            $this->container = new Container();
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
        try {
            // En modo testing, crear un container mock solo si se necesita
            if ($this->testingMode) {
                return; // En testing mode no hacemos nada
            }

            if (!$this->testingMode) {
                new Context($this->container);
            }

            $this->errorConsole = $this->container->get(ErrorConsole::class);
            $this->errorConsole->register();

            // Cargar caché de DTOs mejorados
            $this->loadDTOCache();

            if ($loadRouter) {
                $router = $this->container->get(Router::class);
                $router->initBalero();
            }

        } catch (Throwable $e) {
            throw new BootException("Error in Boot: " . $e->getMessage(), previous: $e);
        }
    }

    /**
     * Carga el caché de DTOs mejorados
     */
    private function loadDTOCache(): void
    {
        if ($this->dtoCacheLoaded || $this->testingMode) {
            return;
        }

        $dtoCacheFile = BASE_PATH . '/cache/dtos.cache.php';

        if (!file_exists($dtoCacheFile)) {
            $this->errorConsole->info("DTOs cache file does not exist: $dtoCacheFile");
            $this->dtoCacheLoaded = true;
            return;
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

        // 1. Verificar si es un DTO mejorado y cargarlo desde caché
        if ($this->loadEnhancedDTO($class)) {
            return;
        }

        // 2. Autoload PSR-4 normal
        $baseDirs = [BASE_PATH . '/'];
        $relativeClass = ltrim($class, '\\');
        $relativePath = str_replace('\\', '/', $relativeClass) . '.php';

        foreach ($baseDirs as $baseDir) {
            $file = $baseDir . $relativePath;
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }

        throw new BootException("Error loading class <code>$class</code><br>Expected: <code>$relativePath</code>");
    }

    /**
     * Intenta cargar un DTO mejorado desde el caché
     *
     * @return bool True si se cargó un DTO mejorado, false si no
     */
    private function loadEnhancedDTO(string $class): bool
    {
        // Si no está en la lista de DTOs mejorados, retornar false
        if (!in_array($class, $this->enhancedDTOs, true)) {
            return false;
        }

        // Extraer el nombre corto de la clase
        $parts = explode('\\', $class);
        $shortClassName = end($parts);

        $enhancedFile = BASE_PATH . '/cache/dtos/' . $shortClassName . '.php';

        if (!file_exists($enhancedFile)) {
            // El caché no existe, permitir que se cargue el original
            return false;
        }

        // Cargar la versión mejorada del caché
        require_once $enhancedFile;
        return true;
    }
}