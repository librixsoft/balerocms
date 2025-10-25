<?php

namespace Framework\Rendering;

use Framework\Rendering\Conditions\ConditionFactory;

class ProcessorIfBlocks
{
    private ConditionFactory $conditionFactory;

    public function __construct(ConditionFactory $conditionFactory)
    {
        $this->conditionFactory = $conditionFactory;
    }

    public function process(string $content, array $flatParams): string
    {
        while (preg_match('/<%--\s*@if\b.*?<%--\s*@endif\s*-->/is', $content)) {
            $content = preg_replace_callback(
                '/<%--\s*@if\s+([^\n]+?)\s*-->(?:(?:(?!<%--\s*@if).)*?)'
                . '(?:<%--\s*@(?:elseif|else)\s*.*?)*?<%--\s*@endif\s*-->/is',
                function ($matches) use ($flatParams) {
                    $fullMatch = $matches[0];

                    // Extraer la condición y contenido del @if
                    if (!preg_match('/<%--\s*@if\s+(.*?)\s*-->(.*?)(?=<%--\s*@(?:elseif|else|endif))/is', $fullMatch, $ifParts)) {
                        return $fullMatch;
                    }

                    $ifExpression = trim($ifParts[1]);
                    $ifBlock = $ifParts[2];

                    // 1. Evaluar la condición principal del @if
                    $condition = $this->conditionFactory->parseExpression($ifExpression);
                    if ($condition->evaluate($flatParams)) {
                        return $this->process($ifBlock, $flatParams);
                    }

                    // 2. Extraer y evaluar todos los @elseif en orden
                    preg_match_all(
                        '/<%--\s*@elseif\s+(.*?)\s*-->(.*?)(?=<%--\s*@(?:elseif|else|endif))/is',
                        $fullMatch,
                        $elseifs,
                        PREG_SET_ORDER
                    );

                    foreach ($elseifs as $elseif) {
                        $elseifExpression = trim($elseif[1]);
                        $elseifBlock = $elseif[2];

                        $elseifCondition = $this->conditionFactory->parseExpression($elseifExpression);
                        if ($elseifCondition->evaluate($flatParams)) {
                            return $this->process($elseifBlock, $flatParams);
                        }
                    }

                    // 3. Si ninguna condición fue verdadera, buscar y retornar el @else
                    if (preg_match('/<%--\s*@else\s*-->(.*?)<%--\s*@endif\s*-->/is', $fullMatch, $elseParts)) {
                        return $this->process($elseParts[1], $flatParams);
                    }

                    // 4. Si no hay @else, retornar vacío
                    return '';
                },
                $content
            );
        }

        return $content;
    }
}