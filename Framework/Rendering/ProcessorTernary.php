<?php

namespace Framework\Rendering;

class ProcessorTernary
{
    public function process(string $content, array $params): string
    {
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

            // CAMBIO: pasar true para preservar llaves en traducciones
            return $this->resolveValue($returnValue, $params, true);
        }, $content);
    }

    /**
     * @param bool $preserveBracesForTranslations Si es true y la variable no existe, mantiene {variable}
     */
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

        // 3. Verificar variables anidadas en $params (como user.name)
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
        }

        // 4. Variable simple
        if (isset($params[$value])) {
            return $params[$value];
        }

        // 5. Si no existe y debemos preservar llaves (para traducciones)
        if ($preserveBracesForTranslations && !str_contains($value, ' ')) {
            return '{' . $value . '}';
        }

        // 6. Literal sin comillas
        return $value;
    }

    private function resolveNestedProperty(string $property, array $params)
    {
        $parts = explode('.', $property);
        $value = $params;

        foreach ($parts as $part) {
            if (is_array($value) && isset($value[$part])) {
                $value = $value[$part];
            } else {
                return $property; // No se pudo resolver
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