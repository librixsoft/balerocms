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
    private Container $container;
    private bool $testingMode = false;

    public function __construct()
    {
        if (!$this->testingMode) {
            spl_autoload_register([$this, "autoloadClass"]);
        }
        $this->container = new Container();
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
            if (!$this->testingMode) {
                new Context($this->container);
            }

            $this->errorConsole = $this->container->get(ErrorConsole::class);

            if (!$this->testingMode) {
                $this->errorConsole->register();
            }

            if ($loadRouter && !$this->testingMode) {
                $router = $this->container->get(Router::class);
                $router->initBalero();
            }

        } catch (Throwable $e) {
            throw new BootException("Error in Boot: " . $e->getMessage(), previous: $e);
        }
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
     * Autoload de clases PSR-4
     */
    public function autoloadClass(string $class): void
    {
        if ($this->testingMode) {
            // Crear clase vacía en memoria para tests
            if (!class_exists($class)) {
                eval("namespace " . substr($class, 0, strrpos($class, '\\')) . "; class " . substr($class, strrpos($class, '\\') + 1) . " {}");
            }
            return;
        }

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
}
