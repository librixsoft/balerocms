<?php

/**
 * Balero CMS
 * Context - Contenedor de dependencias global
 *
 * Proporciona acceso global a servicios registrados en el contenedor,
 * especialmente útil en métodos estáticos o clases que no pasan por DI.
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

namespace Framework\Config;

use Framework\Core\ConfigSettings;
use Framework\Core\Container;
use Framework\Rendering\Conditions\AndCondition;
use Framework\Rendering\Conditions\ConditionFactory;
use Framework\Rendering\Conditions\OrCondition;
use Framework\Rendering\ProcessorFlattenParams;
use Framework\Rendering\ProcessorForEach;
use Framework\Rendering\ProcessorIfBlocks;
use Framework\Services\RedirectService;
use Framework\Static\Constant;
use Framework\Static\Redirect;

class Context
{
    /**
     * Contenedor de dependencias principal de la aplicación.
     *
     * @var Container
     */
    protected static Container $container;

    /**
     * Inicializa el Context con el contenedor y registra todos los servicios globales.
     *
     * - Instancia los prototipos OrCondition y AndCondition.
     * - Crea ConditionFactory a partir de los prototipos.
     * - Registra ProcessorIfBlocks con ConditionFactory.
     * - Registra ProcessorFlattenParams y ProcessorForEach.
     *
     * Esto garantiza que los processors y condiciones estén disponibles
     * globalmente mediante Context::get() y replicando la DI usada en tests.
     *
     * @param Container $container Contenedor de la aplicación
     */
    public static function init(Container $container): void
    {
        self::$container = $container;

        // Instancia prototipos
        $container->registerSingletonInstance(OrCondition::class, new OrCondition());
        $container->registerSingletonInstance(AndCondition::class, new AndCondition());

        /** @var OrCondition $or */
        $or = $container->resolveInstance(OrCondition::class);
        /** @var AndCondition $and */
        $and = $container->resolveInstance(AndCondition::class);

        // ConditionFactory
        $container->registerSingletonInstance(
            ConditionFactory::class,
            new ConditionFactory($or, $and)
        );

        /** @var ConditionFactory $conditionFactory */
        $conditionFactory = $container->resolveInstance(ConditionFactory::class);

        // ProcessorIfBlocks
        $container->registerSingletonInstance(
            ProcessorIfBlocks::class,
            new ProcessorIfBlocks($conditionFactory)
        );

        // ProcessorFlattenParams
        $container->registerSingletonInstance(
            ProcessorFlattenParams::class,
            new ProcessorFlattenParams()
        );

        /** @var ProcessorFlattenParams $flatten */
        $flatten = $container->resolveInstance(ProcessorFlattenParams::class);
        /** @var ProcessorIfBlocks $ifBlocks */
        $ifBlocks = $container->resolveInstance(ProcessorIfBlocks::class);

        // ProcessorForEach
        $container->registerSingletonInstance(
            ProcessorForEach::class,
            new ProcessorForEach($flatten, $ifBlocks)
        );

        $config = new ConfigSettings();

        $container->registerSingletonInstance(
            ConfigSettings::class,
            $config
        );

        $container->registerSingletonInstance(ConfigSettings::class, $config);

        /**
         * Inicializar RedirectService y asociarlo a la fachada Redirect
         * Esto reemplaza la necesidad de tener RedirectService en Context.
         */
        $redirectService = new RedirectService($config);
        Redirect::setInstance($redirectService);

    }

    /**
     * Obtiene un servicio desde el contenedor.
     *
     * Ejemplo de uso:
     * ```php
     * $processor = Context::get(ProcessorForEach::class);
     * ```
     *
     * @template T
     * @param class-string<T> $class
     * @return T
     */
    public static function get(string $class): object
    {
        return self::$container->resolveInstance($class);
    }
}
