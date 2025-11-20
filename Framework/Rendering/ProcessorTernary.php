<?php

namespace Framework\Rendering;

class ProcessorTernary
{
    public function process(string $content, array $params): string
    {
        // Patrón mejorado para capturar expresiones más complejas
        $pattern = '/\{([a-zA-Z0-9_.]+)\s*(==|!=|===|!==)\s*[\'"]?([^\'"?:]*)[\'"]?\s*\?\s*([^:]+)\s*:\s*([^}]+)\}/';

        return preg_replace_callback($pattern, function($matches) use ($params) {
            $variable = trim($matches[1]);
            $operator = trim($matches[2]);
            $compareValue = trim($matches[3]);
            $trueValue = trim($matches[4]);
            $falseValue = trim($matches[5]);

            $currentValue = $this->resolveValue($variable, $params, false);
            $result = $this->evaluateCondition($currentValue, $operator, $compareValue);
            $returnValue = $result ? $trueValue : $falseValue;

            // Resolver concatenaciones antes de retornar
            return $this->resolveConcatenation($returnValue, $params);
        }, $content);
    }

    /**
     * Resuelve concatenaciones como: './admin/blocks/edit/' + block.id
     */
    private function resolveConcatenation(string $value, array $params): string
    {
        $value = trim($value);

        // Si contiene +, es una concatenación
        if (str_contains($value, '+')) {
            $parts = explode('+', $value);
            $result = '';

            foreach ($parts as $part) {
                $part = trim($part);

                // Si es un string literal entre comillas, extraer el contenido
                if (preg_match('/^[\'"](.*)[\'"]\s*$/', $part, $match)) {
                    $result .= $match[1];
                }
                // Si es una variable (ej: block.id)
                elseif (preg_match('/^[a-zA-Z0-9_.]+$/', $part)) {
                    $resolved = $this->resolveNestedProperty($part, $params);
                    // Si se resolvió, usar el valor
                    if ($resolved !== $part && !is_array($resolved)) {
                        $result .= $resolved;
                    }
                }
                // Cualquier otro caso, agregar tal cual (sin comillas)
                else {
                    $result .= str_replace(['"', "'"], '', $part);
                }
            }

            return $result;
        }

        // Si no hay concatenación, resolver normalmente
        return $this->resolveValue($value, $params, true);
    }

    private function resolveValue(string $value, array $params, bool $preserveBracesForTranslations = false)
    {
        $value = trim($value);

        // 1. Caso especial: comillas vacías
        if ($value === "''" || $value === '""') {
            return '';
        }

        // 2. String literal entre comillas
        if (preg_match('/^[\'"](.+)[\'"]$/', $value, $match)) {
            return $match[1];
        }

        // 3. Verificar variables anidadas en $params (como block.name)
        if (str_contains($value, '.')) {
            $resolved = $this->resolveNestedProperty($value, $params);

            // Si se resolvió correctamente, retornar el valor
            if ($resolved !== $value) {
                return $resolved;
            }

            // Si NO se resolvió y debemos preservar llaves, retornar con llaves
            if ($preserveBracesForTranslations) {
                return '{' . $value . '}';
            }

            // Si no existe y no debemos preservar, retornar string vacío
            return '';
        }

        // 4. Variable simple
        if (isset($params[$value])) {
            return $params[$value];
        }

        // 5. Si no existe y debemos preservar llaves (para traducciones)
        if ($preserveBracesForTranslations && !str_contains($value, ' ')) {
            return '{' . $value . '}';
        }

        // 6. Si no existe, retornar string vacío en lugar del valor literal
        return '';
    }

    private function resolveNestedProperty(string $property, array $params)
    {
        // Primero intentar buscar la clave aplanada directamente (ej: "block.id")
        if (isset($params[$property])) {
            return $params[$property];
        }

        // Si no existe, intentar navegar como array anidado
        $parts = explode('.', $property);
        $value = $params;

        foreach ($parts as $part) {
            if (is_array($value) && isset($value[$part])) {
                $value = $value[$part];
            } else {
                return $property;
            }
        }

        return $value;
    }

    private function evaluateCondition($left, string $operator, $right): bool
    {
        return match ($operator) {
            '==' => $left == $right,
            '!=' => $left != $right,
            '===' => $left === $right,
            '!==' => $left !== $right,
            default => false,
        };
    }
}