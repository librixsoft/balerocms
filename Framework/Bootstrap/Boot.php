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

    public function __construct(bool $loadRouter = true)
    {
        try {

            spl_autoload_register([$this, "autoloadClass"]);

            $this->container = new Container();

            $context = new Context($this->container);

            $this->errorConsole = $this->container->get(ErrorConsole::class);
            $this->errorConsole->register();

            if ($loadRouter) {
                $router = $this->container->get(Router::class);
                $router->initBalero(); // sin callback
            }

        } catch (Throwable $e) {
            throw new BootException("Error in Boot: " . $e->getMessage(), previous: $e);
        }
    }

    public function autoloadClass(string $class): void
    {
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

        throw new BootException("Error loading class <code>$class</code><br>Expected: <code>$relativePath</code>");
    }

    public function getFromContainer(string $class): object
    {
        return $this->container->get($class);
    }

    public function getContainer(): Container
    {
        return $this->container;
    }
}
