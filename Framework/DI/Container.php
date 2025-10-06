<?php

namespace Framework\DI;

use Framework\Attributes\Controller;
use Framework\Core\BaseController;
use Framework\Exceptions\ContainerException;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

class Container
{
    private array $bindings = [];

    public function set(string $id, object $instance): void
    {
        $this->bindings[$id] = $instance;
    }

    public function get(string $className): object
    {
        try {
            // --- Return singleton if exists ---
            if (isset($this->bindings[$className])) {
                return $this->bindings[$className];
            }

            // --- Create instance using DependencyFactory ---
            $factory = new DependencyFactory($this);
            $instance = $factory->create($className);

            // --- Controller logic ---
            $reflector = new ReflectionClass($className);
            $controllerAttrs = $reflector->getAttributes(Controller::class);
            if (!empty($controllerAttrs) && str_starts_with($className, 'App')) {
                $baseController = $this->get(BaseController::class);
                $method = new ReflectionMethod(BaseController::class, 'initControllerAndRoute');
                $method->invoke($baseController, $instance);
            }

            return $instance;
        } catch (Throwable $e) {
            throw new ContainerException(
                "Error resolving {$className}: " . $e->getMessage(),
                0,
                $e
            );
        }
    }
}
