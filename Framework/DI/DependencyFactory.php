<?php

/**
 * Balero CMS - Dependency Factory
 *
 * Centraliza la creación de instancias de clases, soportando:
 * - Inyección por constructor
 * - Inyección de propiedades marcadas con #[Inject]
 * - Resolución del atributo #[FlashStorage] para Flash
 * - Resolución delegada a un contenedor que implementa `get(string $className): object`
 *
 * @author Anibal Gomez
 * @license GNU General Public License
 */

namespace Framework\DI;

use Framework\Attributes\FlashStorage;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionMethod;
use Framework\Attributes\Inject;
use Framework\Utils\Flash;
use Framework\Exceptions\ContainerException;
use Throwable;

class DependencyFactory
{
    /**
     * Contenedor que implementa `get(string $className): object`
     * Puede ser Container o Context
     *
     * @var object
     */
    private object $resolverContainer;

    /**
     * Constructor
     *
     * @param object $resolverContainer Contenedor con método get() (Container o Context)
     */
    public function __construct(object $resolverContainer)
    {
        $this->resolverContainer = $resolverContainer;
    }

    /**
     * Crea una instancia de clase con inyección por constructor
     *
     * @param ReflectionClass $reflector
     * @param ReflectionMethod $constructor
     * @return object
     * @throws ContainerException
     */
    public function createWithConstructor(ReflectionClass $reflector, ReflectionMethod $constructor): object
    {
        try {
            $params = [];
            foreach ($constructor->getParameters() as $param) {
                $type = $param->getType();

                if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                    $params[] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
                    continue;
                }

                $params[] = $this->resolverContainer->get($type->getName());
            }

            return $reflector->newInstanceArgs($params);
        } catch (Throwable $e) {
            throw new ContainerException(
                "Failed to create instance of {$reflector->getName()} using constructor injection: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Crea una instancia sin pasar parámetros al constructor
     *
     * @param ReflectionClass $reflector
     * @return object
     * @throws ContainerException
     */
    public function createWithoutConstructor(ReflectionClass $reflector): object
    {
        try {
            return $reflector->newInstance();
        } catch (Throwable $e) {
            throw new ContainerException(
                "Failed to create instance of {$reflector->getName()} without constructor: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Inyecta dependencias en propiedades #[Inject]
     *
     * @param object $instance
     * @param ReflectionClass $reflector
     * @return void
     * @throws ContainerException
     */
    public function injectProperties(object $instance, ReflectionClass $reflector): void
    {
        try {
            foreach ($reflector->getProperties() as $prop) {
                $attrs = $prop->getAttributes(Inject::class);
                if (empty($attrs)) continue;

                $type = $prop->getType();
                if (!$type instanceof ReflectionNamedType) continue;

                $flashStorageAttrs = $prop->getAttributes(FlashStorage::class);

                $prop->setAccessible(true);

                $prop->setValue($instance, $this->resolverContainer->get($type->getName()));
            }
        } catch (Throwable $e) {
            throw new ContainerException(
                "Failed to inject properties into {$reflector->getName()}: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Crea la instancia completa de la clase (constructor + #[Inject])
     *
     * @param string $className
     * @return object
     * @throws ContainerException
     */
    public function create(string $className): object
    {
        try {
            $reflector = new ReflectionClass($className);
            $constructor = $reflector->getConstructor();

            $instance = ($constructor && $constructor->getNumberOfParameters() > 0)
                ? $this->createWithConstructor($reflector, $constructor)
                : $this->createWithoutConstructor($reflector);

            $this->injectProperties($instance, $reflector);

            return $instance;
        } catch (Throwable $e) {
            throw new ContainerException(
                "Failed to create instance of {$className}: {$e->getMessage()}",
                0,
                $e
            );
        }
    }
}