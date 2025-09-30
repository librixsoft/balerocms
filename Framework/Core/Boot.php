<?php

namespace Framework\Core;

use Framework\Exceptions\BootException;
use Framework\Routing\Router;
use Framework\Core\Container;
use Framework\Config\Context;
use Throwable;

class Boot
{
    private ErrorConsole $errorConsole;
    private Container $container;

    public function __construct(bool $loadRouter = true)
    {
        try {
            // Autoload
            spl_autoload_register([$this, "autoloadClass"]);


            // Container
            $this->container = new Container();

            // Context se obtiene del container
            $context = new Context($this->container);

            $view = $this->container->get(View::class);
            $config = $this->container->get(ConfigSettings::class);
            $errorConsole = new ErrorConsole($view, $config); // directamente
            $errorConsole->register();


            // Inicializar Router
            if ($loadRouter) {
                $router = new Router();
                $router->initBalero(
                    $this->container->get(\Framework\Http\RequestHelper::class),
                    $this->container->get(\Framework\Core\ConfigSettings::class),
                    fn(string $class) => $this->getFromContainer($class)
                );
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
