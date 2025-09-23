<?php

namespace Framework\Rendering;

class ProcessorForEach
{
    private ProcessorFlattenParams $processFlattenParams;
    private ProcessorIfBlocks $processorIfBlocks;

    public function __construct(
        ProcessorFlattenParams $processFlattenParams,
        ProcessorIfBlocks $processorIfBlocks
    )
    {
        $this->processFlattenParams = $processFlattenParams;
        $this->processorIfBlocks = $processorIfBlocks;
    }

    /**
     * Procesa bloques:
     * @foreach var as item
     * @foreach var as key => value
     */
    public function process(string $content, array $params): string
    {
        return preg_replace_callback(
        // Captura `@foreach array as item` o `@foreach array as key => value`
            '/<%--\s*@foreach\s+(\w+)\s+as\s+(\w+)(?:\s*=>\s*(\w+))?\s*-->(.*?)<%--\s*@endforeach\s*-->/is',
            function ($matches) use ($params) {
                $arrayKey = $matches[1]; // ej: 'errors'
                $firstVar = $matches[2]; // ej: 'field'
                $secondVar = $matches[3] ?? null; // ej: 'message'
                $block = $matches[4]; // contenido dentro del foreach

                if (!isset($params[$arrayKey]) || !is_array($params[$arrayKey])) {
                    return ''; // No existe o no es array
                }

                $result = '';
                foreach ($params[$arrayKey] as $k => $v) {
                    if ($secondVar) {
                        // Caso: key => value
                        $item = [
                            $firstVar => $k,
                            $secondVar => $v
                        ];
                    } else {
                        // Caso simple: item
                        $item = [$firstVar => $v];
                    }

                    // Aplanar parámetros (ej: ['field'=>'username', 'message'=>'...'])
                    $flatItem = $this->processFlattenParams->process($item);

                    // Reemplazar placeholders en el bloque
                    $blockCopy = $block;
                    foreach ($flatItem as $k2 => $v2) {
                        $blockCopy = str_replace('{' . $k2 . '}', (string)$v2, $blockCopy);
                    }

                    // Evaluar ifs dentro del bloque
                    $result .= $this->processorIfBlocks->process($blockCopy, $params + $flatItem);
                }

                return $result;
            },
            $content
        );
    }
}
