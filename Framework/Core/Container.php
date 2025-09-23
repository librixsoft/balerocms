<?php

/**
 * Balero CMS
 * @author Anibal Gomez <balerocms@gmail.com>
 * @license GNU General Public License
 */

namespace Framework\Core;

use Closure;

class Container
{
    /**
     * Array que asocia identificadores de clase o interfaz a closures que crean las instancias.
     *
     * @var array<string, Closure>
     */
    private array $bindings = [];

    /**
     * Registra una instancia singleton para un identificador dado.
     *
     * Cada vez que se solicite este identificador, se devolverá siempre la misma instancia.
     *
     * @param string $id Nombre de clase o interfaz.
     * @param object $instance Instancia única a devolver.
     * @return void
     */
    public function registerSingletonInstance(string $id, object $instance): void
    {
        $this->bindings[$id] = fn() => $instance;
    }

    /**
     * Resuelve y devuelve una instancia de la clase o interfaz indicada.
     *
     * Flujo de resolución:
     * 1. Si existe un binding o singleton registrado, lo devuelve.
     * 2. Si no, analiza el constructor y crea instancias de sus dependencias automáticamente.
     * 3. Después de instanciar el objeto, inyecta automáticamente todas las propiedades
     *    marcadas con el atributo #[Inject] usando la resolución del contenedor.
     *
     * Lanza excepción si no puede instanciar o resolver dependencias no tipadas o escalares.
     *
     * @param string $id Nombre de la clase o interfaz a instanciar
     * @return object Instancia resuelta con constructor y propiedades inyectadas
     *
     * @throws \Exception Si no se puede crear la instancia o resolver dependencias
     */
    public function resolveInstance(string $id): object
    {
        if (str_starts_with($id, 'Framework\\Static\\')) {
            throw new \Exception("La clase estática {$id} no puede ser instanciada");
        }

        // Retorna singleton o binding registrado
        if (isset($this->bindings[$id])) {
            return ($this->bindings[$id])();
        }

        $reflector = new \ReflectionClass($id);
        if (!$reflector->isInstantiable()) {
            throw new \Exception("La clase {$id} no es instanciable");
        }

        // Resolver constructor
        $constructor = $reflector->getConstructor();
        $dependencies = [];
        if ($constructor) {
            foreach ($constructor->getParameters() as $param) {
                $type = $param->getType();
                if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
                    throw new \Exception("No se puede resolver el parámetro {$param->getName()} en {$id}");
                }
                $dependencies[] = $this->resolveInstance($type->getName());
            }
        }

        $instance = $reflector->newInstanceArgs($dependencies);

        // Inyección automática de propiedades marcadas con #[Inject]
        foreach ($reflector->getProperties() as $property) {
            $attributes = $property->getAttributes(Inject::class);
            if (!empty($attributes)) {
                $propType = $property->getType();
                if (!$propType instanceof \ReflectionNamedType || $propType->isBuiltin()) {
                    throw new \Exception("No se puede inyectar la propiedad {$property->getName()} en {$id}");
                }
                $property->setAccessible(true);
                $property->setValue($instance, $this->resolveInstance($propType->getName()));
            }
        }

        return $instance;
    }

}
