<?php

namespace Framework\Core;

use Framework\Exceptions\BootException;
use Framework\Routing\Router;
use Throwable;

class Boot
{
    private ErrorConsole $errorConsole;
    private ?Router $router;

    /**
     * Boot constructor.
     *
     * @param Router|null $router Router opcional para test
     */
    public function __construct(?Router $router = null, bool $loadRouter = true)
    {
        $this->router = $router;

        try {
            // Registrar autoload
            spl_autoload_register([$this, "autoloadClass"]);

            // Inicializar ErrorConsole
            $this->errorConsole = new ErrorConsole();
            $this->errorConsole->register();

            // Inicializar Router solo si no estamos en test
            if ($loadRouter) {
                if ($this->router === null) {
                    $this->router = new Router();
                }
                $this->router->initBalero();
            }

        } catch (Throwable $e) {
            throw new BootException(
                "Error in Boot: " . $e->getMessage(),
                previous: $e
            );
        }
    }

    public function autoloadClass(string $class): void
    {
        try {
            $baseDirs = [LOCAL_DIR . '/'];

            $relativeClass = ltrim($class, '\\');
            $relativePath = str_replace('\\', '/', $relativeClass) . '.php';

            if (str_starts_with($relativePath, 'Modules/Modules/')) {
                $relativePath = substr($relativePath, strlen('Modules/'));
            }

            foreach ($baseDirs as $baseDir) {
                $file = $baseDir . $relativePath;
                if (file_exists($file)) {
                    require_once $file;
                    return;
                }
            }

            $message = "Error loading class <code>$class</code><br>Expected: <code>$relativePath</code>";
            throw new BootException($message);

        } catch (Throwable $e) {
            throw new BootException(
                "Autoload fails: " . $e->getMessage(),
                previous: $e
            );
        }
    }
}
