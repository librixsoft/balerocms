<?php

/**
 * Balero CMS
 * Context - Contenedor de dependencias global
 *
 * Proporciona acceso global a servicios registrados en el contenedor,
 * especialmente útil en clases que no pasan por DI directamente.
 *
 * Esta clase registra los processors principales y las condiciones necesarias
 * para la evaluación de templates:
 * - Se crean prototipos de OrCondition y AndCondition.
 * - Se instancia ConditionFactory pasando los prototipos.
 * - Se instancia ProcessorIfBlocks con ConditionFactory.
 * - Se instancian ProcessorFlattenParams y ProcessorForEach, pasando
 *   ProcessorIfBlocks donde corresponde.
 *
 * Nota: Esta misma forma de instanciación se usa en los tests unitarios
 *       de ProcessorForEachTest, por lo que el contexto refleja el mismo
 *       flujo de dependencias que los tests.
 *
 * @author Anibal Gomez
 * @license GNU General Public License
 */

namespace Framework\DI;

use Framework\Config\ViewConfig;
use Framework\Config\SetupConfig;
use Framework\Core\ConfigSettings;
use Framework\DI\Container;
use Framework\Core\View;
use Framework\Core\ErrorConsole;
use Framework\Utils\Redirect;

class Context
{
    /**
     * Contenedor de dependencias principal de la aplicación.
     *
     * @var Container
     */
    private Container $container;

    /**
     * Constructor de Context.
     *
     * Aquí se inicializan y registran todos los servicios globales
     * necesarios para la aplicación (processors, conditions, etc.).
     *
     * @param Container $container Contenedor de la aplicación
     */
    public function __construct(Container $container)
    {
        $this->container = $container;

        $container->singleton(SetupConfig::class, function() {
            return new SetupConfig(BASE_PATH . '/resources/config/balero.config.json');
        });

        $container->singleton(ConfigSettings::class, function() use ($container) {
            return new ConfigSettings($container->get(SetupConfig::class));
        });

        $config = $container->get(ConfigSettings::class);
        $config->getHandler();

        $container->singleton(ViewConfig::class, function() {
            return new ViewConfig(
                BASE_PATH . '/resources/views',
                BASE_PATH . '/resources/lang',
                ['html']
            );
        });

        $view = $container->get(View::class);

        $container->set(View::class, $view);

        $errorConsole = new ErrorConsole($view, $config);
        $container->set(ErrorConsole::class, $errorConsole);

        $redirect = new Redirect($config);
        $container->set(Redirect::class, $redirect);

    }

    /**
     * Obtiene un servicio desde el contenedor.
     *
     * Ejemplo de uso:
     * ```php
     * $processor = $context->get(ProcessorForEach::class);
     * ```
     *
     * @template T
     * @param class-string<T> $class
     * @return T
     */
    public function get(string $class): object
    {
        return $this->container->get($class);
    }
}
