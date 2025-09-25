<?php

/**
 * Balero CMS
 *
 * Clase Boot
 *
 * Punto de entrada del framework:
 * - Registra autoload de clases
 * - Inicializa contenedor de dependencias
 * - Inicializa el contexto global de la aplicación
 * - Registra manejador de errores
 *
 * Flujo de dependencias:
 *
 * index.php
 *     │
 *     ▼
 * +-------------------+
 * |      Boot         |
 * |-------------------|
 * | - container       |───► instancia de Container
 * | - context         |───► instancia de Context(container)
 * +-------------------+
 *     │
 *     │ $boot->getFromContainer(Router::class)
 *     ▼
 * +-------------------+
 * |      Router       |
 * |-------------------|
 * | __construct(      |
 * |   ConfigSettings, │◄── resuelto automáticamente desde Container
 * |   RequestHelper,  │◄── resuelto automáticamente desde Container
 * |   Boot            │◄── inyectado la misma instancia de Boot
 * | )                 |
 * +-------------------+
 *     │
 *     ▼ initBalero()
 * +-------------------+
 * |   Controller(s)   |
 * |-------------------|
 * | - initControllerAndInject() │◄── opcional, se ejecuta tras constructor
 * | - todas las dependencias resueltas automáticamente
 * +-------------------+
 *
 * @author Anibal Gomez
 * @license GNU General Public License
 */

namespace Framework\Core;

use Framework\Config\Context;
use Framework\Core\ErrorConsole;

class Boot
{
    /**
     * Contenedor de dependencias para toda la aplicación.
     *
     * // CHANGE: ya no es estático
     */
    private Container $container;

    /**
     * Contexto de la aplicación (ya no estático)
     *
     * // CHANGE: almacenamos la instancia de Context
     */
    private Context $context;

    /**
     * Inicializa el framework:
     * - Registra autoload de clases
     * - Inicializa contenedor y Context
     * - Registra manejador de errores
     */
    public function __construct()
    {
        spl_autoload_register([$this, "autoloadClass"]);

        $this->container = new Container();

        ErrorConsole::register();

        $this->context = new Context($this->container);
    }

    /**
     * Carga un controller y aplica DI.
     *
     * @param string $controllerClass
     */
    public function loadController(string $controllerClass): void
    {
        try {
            // Instancia el controller usando DI automática
            $instance = $this->getFromContainer($controllerClass);

            // Ejecuta lógica post-constructor opcional
            if (method_exists($instance, 'initControllerAndInject')) {
                $instance->initControllerAndInject();
            }

        } catch (\Throwable $e) {
            ErrorConsole::handleException(
                new \Exception(
                    "Error cargando controller '$controllerClass': " . $e->getMessage(),
                    0,
                    $e
                )
            );
            exit;
        }
    }

    /**
     * Instancia cualquier clase usando el contenedor.
     * No realiza lógica extra ni inyecciones automáticas fuera del contenedor.
     *
     * @param string $class
     * @return object Instancia creada
     *
     * // CHANGE: ya no es static
     */
    public function getFromContainer(string $class): object
    {
        return $this->container->get($class);
    }

    /**
     * Getter para el contenedor.
     *
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Getter para el Context de la aplicación.
     *
     * @return Context
     */
    public function getContext(): Context
    {
        return $this->context;
    }

    /**
     * Autocarga una clase PHP dado su namespace.
     *
     * @param string $class
     */
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

        $message = "No se pudo cargar la clase <code>$class</code><br>Ruta esperada: <code>$relativePath</code>";
        ErrorConsole::handleException(new \Exception($message));
    }
}
