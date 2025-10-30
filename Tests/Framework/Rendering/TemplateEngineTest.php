<?php

namespace Tests\Framework\Rendering;

use Framework\Rendering\ProcessorFlattenParams;
use Framework\Rendering\ProcessorForEach;
use Framework\Rendering\ProcessorIfBlocks;
use Framework\Rendering\ProcessorIncludes;
use Framework\Rendering\ProcessorKeyPath;
use Framework\Rendering\ProcessorVariables;
use Framework\Rendering\ProcessorTernary;
use Framework\Rendering\TemplateEngine;
use PHPUnit\Framework\TestCase;

class TemplateEngineTest extends TestCase
{
    public function testProcessTemplateFunctional(): void
    {
        $engine = $this->createTemplateEngineWithMocks();

        $engine->setBaseDir('/base/path');

        $template = 'Hola, {{ nombre }}!';
        $params = ['nombre' => 'Aníbal'];

        $output = $engine->processTemplate($template, $params);

        $expected = 'Hola, Aníbal![Includes:/base/path/][ForEach][Ternary][Variables][IfBlocks][KeyPath]';

        $this->assertSame($expected, $output);
    }

    private function createTemplateEngineWithMocks(): TemplateEngine
    {
        $processorIncludes = $this->createMock(ProcessorIncludes::class);
        $processorFlattenParams = $this->createMock(ProcessorFlattenParams::class);
        $processorForEach = $this->createMock(ProcessorForEach::class);
        $processorIfBlocks = $this->createMock(ProcessorIfBlocks::class);
        $processorVariables = $this->createMock(ProcessorVariables::class);
        $processorKeyPath = $this->createMock(ProcessorKeyPath::class);
        $processorTernary = $this->createMock(ProcessorTernary::class);

        // Comportamientos simulados
        $processorIncludes->method('process')->willReturnCallback(fn($content, $baseDir) => $content . '[Includes:' . ($baseDir ?? '') . ']');
        $processorFlattenParams->method('process')->willReturnCallback(fn($params) => $params);
        $processorForEach->method('process')->willReturnCallback(fn($content, $params) => $content . '[ForEach]');
        $processorTernary->method('process')->willReturnCallback(fn($content, $flatParams) => $content . '[Ternary]');
        $processorVariables->method('process')->willReturnCallback(fn($content, $flatParams) => str_replace('{{ nombre }}', $flatParams['nombre'] ?? '', $content) . '[Variables]');
        $processorIfBlocks->method('process')->willReturnCallback(fn($content, $flatParams) => $content . '[IfBlocks]');
        $processorKeyPath->method('process')->willReturnCallback(fn($content, $flatParams) => $content . '[KeyPath]');

        return new TemplateEngine(
            $processorIncludes,
            $processorFlattenParams,
            $processorForEach,
            $processorIfBlocks,
            $processorVariables,
            $processorKeyPath,
            $processorTernary
        );
    }
}