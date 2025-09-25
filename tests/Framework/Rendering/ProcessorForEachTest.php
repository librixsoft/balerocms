<?php

use Framework\Rendering\Conditions\AndCondition;
use Framework\Rendering\Conditions\ConditionFactory;
use Framework\Rendering\Conditions\OrCondition;
use Framework\Rendering\Conditions\NotCondition;
use Framework\Rendering\Conditions\EqualsCondition;
use Framework\Rendering\Conditions\NotEqualsCondition;
use Framework\Rendering\Conditions\TruthyCondition;
use Framework\Rendering\ProcessorFlattenParams;
use Framework\Rendering\ProcessorForEach;
use Framework\Rendering\ProcessorIfBlocks;
use PHPUnit\Framework\TestCase;

class ProcessorForEachTest extends TestCase
{
    private ProcessorForEach $processorForEach;
    private ProcessorIfBlocks $processorIfBlocks;
    private $mockFlatten;
    private string $viewsDir;

    protected function setUp(): void
    {
        // Mock ProcessorFlattenParams
        $this->mockFlatten = $this->createMock(ProcessorFlattenParams::class);
        $this->mockFlatten->method('process')
            ->willReturnCallback(function ($array) {
                $flat = [];
                foreach ($array as $k => $v) {
                    if (is_array($v)) {
                        foreach ($v as $subk => $subv) {
                            $flat["$k.$subk"] = $subv;
                        }
                    } else {
                        $flat[$k] = $v;
                    }
                }
                return $flat;
            });

        // Crear prototipos de Or y And
        $orPrototype = new OrCondition();
        $andPrototype = new AndCondition();

        // Crear instancias de condiciones simples
        $notCondition = new NotCondition();
        $equalsCondition = new EqualsCondition();
        $notEqualsCondition = new NotEqualsCondition();
        $truthyCondition = new TruthyCondition();

        // Crear la fábrica de condiciones inyectando todas las dependencias
        $conditionFactory = new ConditionFactory(
            $orPrototype,
            $andPrototype,
            $notCondition,
            $equalsCondition,
            $notEqualsCondition,
            $truthyCondition
        );

        // Crear ProcessorIfBlocks con la fábrica
        $this->processorIfBlocks = new ProcessorIfBlocks($conditionFactory);

        // Crear ProcessorForEach con sus dependencias
        $this->processorForEach = new ProcessorForEach(
            $this->mockFlatten,
            $this->processorIfBlocks
        );

        $this->viewsDir = __DIR__ . '/../../resources/views/foreach/';
    }

    // --- Tests existentes se mantienen iguales ---
    public function testForeachSimple()
    {
        $template = $this->loadTemplate('foreach_simple.html');

        $params = [
            'items' => [
                ['name' => 'One', 'value' => 10],
                ['name' => 'Two', 'value' => 20],
            ]
        ];

        $result = $this->processorForEach->process($template, $params);
        $resultNormalized = preg_replace('/\s+/', ' ', $result);

        $this->assertStringContainsString('Item: One, Value: 10', $resultNormalized);
        $this->assertStringContainsString('Item: Two, Value: 20', $resultNormalized);
    }

    private function loadTemplate(string $filename): string
    {
        $path = $this->viewsDir . $filename;
        if (!file_exists($path)) {
            throw new \RuntimeException("Template file not found: $path");
        }
        return file_get_contents($path);
    }

    // --- Resto de tests se mantienen iguales ---
}
