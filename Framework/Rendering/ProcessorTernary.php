<?php

namespace Framework\Rendering;

class ProcessorTernary
{
    /**
     * Procesa expresiones ternarias en el template
     * Ejemplo: {activeMenu == 'settings' ? 'active' : ''}
     */
    public function process(string $content, array $params): string
    {
        // Patrón para capturar expresiones ternarias dentro de llaves
        $pattern = '/\{([a-zA-Z0-9_]+)\s*(==|!=|===|!==)\s*[\'"]([^\'"]*)[\'"]?\s*\?\s*[\'"]([^\'"]*)[\'"]?\s*:\s*[\'"]([^\'"]*)[\'"]?\}/';

        return preg_replace_callback($pattern, function($matches) use ($params) {
            $variable = $matches[1];      // activeMenu
            $operator = $matches[2];      // ==
            $compareValue = $matches[3];  // 'settings'
            $trueValue = $matches[4];     // 'active'
            $falseValue = $matches[5];    // ''

            $currentValue = $params[$variable] ?? '';

            $result = false;
            switch ($operator) {
                case '==':
                    $result = $currentValue == $compareValue;
                    break;
                case '!=':
                    $result = $currentValue != $compareValue;
                    break;
                case '===':
                    $result = $currentValue === $compareValue;
                    break;
                case '!==':
                    $result = $currentValue !== $compareValue;
                    break;
            }

            return $result ? $trueValue : $falseValue;
        }, $content);
    }
}