<?php

namespace Framework\DTO;

use Framework\DTO\Attributes\Getter;
use Framework\DTO\Attributes\Setter;
use Framework\DTO\Attributes\ToArray;
use ReflectionClass;

class DTOGenerator
{
    private array $generatedClasses = [];

    /**
     * Crea una instancia mejorada de un DTO
     * Usar con inyección de dependencias: $this->dtoGenerator->create(SettingsDTO::class)
     */
    public function create(string $dtoClass, ...$args): object
    {
        $instance = new $dtoClass(...$args);

        $ref = new ReflectionClass($instance);

        $hasGetter = !empty($ref->getAttributes(Getter::class));
        $hasSetter = !empty($ref->getAttributes(Setter::class));
        $hasToArray = !empty($ref->getAttributes(ToArray::class));

        if (!$hasGetter && !$hasSetter && !$hasToArray) {
            return $instance;
        }

        return $this->createEnhancedDTO($instance);
    }

    private function createEnhancedDTO(object $dto): object
    {
        $originalClass = get_class($dto);
        $ref = new ReflectionClass($dto);
        $namespace = $ref->getNamespaceName();

        if (isset($this->generatedClasses[$originalClass])) {
            return $this->copyToEnhanced($dto, $this->generatedClasses[$originalClass]);
        }

        $hasGetter = !empty($ref->getAttributes(Getter::class));
        $hasSetter = !empty($ref->getAttributes(Setter::class));
        $hasToArray = !empty($ref->getAttributes(ToArray::class));

        // Generar nombre único que NO será buscado por el autoloader
        $uniqueId = substr(md5($originalClass), 0, 8);
        $shortClassName = $ref->getShortName();
        $enhancedClassName = $shortClassName . 'Enhanced' . $uniqueId;
        $code = $this->generateClassCode($originalClass, $enhancedClassName, $ref, $hasGetter, $hasSetter, $hasToArray);

        eval($code);

        // Guardar el nombre completo con namespace
        $fullEnhancedClassName = $namespace . '\\' . $enhancedClassName;
        $this->generatedClasses[$originalClass] = $fullEnhancedClassName;

        return $this->copyToEnhanced($dto, $fullEnhancedClassName);
    }

    private function generateClassCode(
        string $originalClass,
        string $enhancedClassName,
        ReflectionClass $ref,
        bool $hasGetter,
        bool $hasSetter,
        bool $hasToArray
    ): string {
        $namespace = $ref->getNamespaceName();
        $methods = [];

        foreach ($ref->getProperties() as $property) {
            $name = $property->getName();
            $camel = ucfirst($name);

            if ($hasGetter) {
                $methods[] = "    public function get{$camel}() { return \$this->{$name}; }";
            }

            if ($hasSetter) {
                $methods[] = "    public function set{$camel}(\$value) { \$this->{$name} = \$value; return \$this; }";
            }
        }

        if ($hasToArray) {
            $methods[] = "    public function toArray(): array {";
            $methods[] = "        \$ref = new \\ReflectionClass(\$this);";
            $methods[] = "        \$result = [];";
            $methods[] = "        foreach (\$ref->getProperties() as \$prop) {";
            $methods[] = "            \$prop->setAccessible(true);";
            $methods[] = "            \$result[\$prop->getName()] = \$prop->getValue(\$this);";
            $methods[] = "        }";
            $methods[] = "        return \$result;";
            $methods[] = "    }";
        }

        $methodsCode = implode("\n\n", $methods);

        // Generar clase en el MISMO namespace
        return <<<PHP
namespace {$namespace} {
    class {$enhancedClassName} extends {$ref->getShortName()}
    {
{$methodsCode}
    }
}
PHP;
    }

    private function copyToEnhanced(object $source, string $targetClass): object
    {
        $target = new $targetClass();
        $ref = new ReflectionClass($source);

        foreach ($ref->getProperties() as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($source);
            $property->setValue($target, $value);
        }

        return $target;
    }
}