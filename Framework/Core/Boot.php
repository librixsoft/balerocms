<?php

/**
 * Balero CMS
 * @author Anibal Gomez <balerocms@gmail.com>
 * @license GNU General Public License
 */

namespace Framework\Core;

use Framework\Config\Context;

class Boot
{
    /**
     * Contenedor de dependencias estático para toda la aplicación.
     */
    public static Container $container;

    /**
     * Inicializa el framework:
     * - Registra el autoload de clases.
     * - Registra el manejador de errores personalizado.
     * - Inicializa configuraciones globales.
     * - Instancia el contenedor de dependencias.
     */
    public function __construct()
    {
        spl_autoload_register([$this, "autoloadClass"]);

        // Primero: instancia el contenedor
        self::$container = new Container();

        ErrorConsole::register();

        // Global services
        Context::init(self::$container);

    }

    /**
     * LLama el metodo initControllerAndInject() de la clase padre
     * Ejecuta la DI adicionalmente al constructor con el metodo initControllerAndInject()
     * @param string $controllerClass
     */
    public static function loadController(string $controllerClass): void
    {
        try {
            // Instancia el controller usando DI automática en el constructor
            $instance = self::instantiateClass($controllerClass);

            // Ejecuta lógica post-constructor opcional
            if (method_exists($instance, 'initControllerAndInject')) {
                // initControllerAndInject no recibe parámetros; obtiene dependencias desde Context
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
     * Instancia cualquier clase pasando argumentos opcionales.
     * No realiza lógica extra ni inyecciones automáticas.
     *
     * @return object Instancia creada.
     */
    public static function instantiateClass(string $class): object
    {
        return self::$container->resolveInstance($class);
    }

    /**
     * Autocarga una clase PHP dado su namespace.
     * Busca el archivo PHP correspondiente dentro de los directorios base definidos.
     *
     * @param string $class Nombre completo del namespace + clase.
     * @return void
     */
    public function autoloadClass(string $class): void
    {
        $baseDirs = [
            LOCAL_DIR . '/',
        ];

        $relativeClass = ltrim($class, '\\');
        $relativePath = str_replace('\\', '/', $relativeClass) . '.php';

        // Corrección en caso de doble prefijo 'Modules/Modules/'
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
