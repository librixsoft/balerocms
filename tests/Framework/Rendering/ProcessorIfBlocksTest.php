<?php

use Framework\Rendering\Conditions\AndCondition;
use Framework\Rendering\Conditions\OrCondition;
use Framework\Rendering\Conditions\NotCondition;
use Framework\Rendering\Conditions\EqualsCondition;
use Framework\Rendering\Conditions\NotEqualsCondition;
use Framework\Rendering\Conditions\TruthyCondition;
use Framework\Rendering\Conditions\ConditionFactory;
use Framework\Rendering\ProcessorIfBlocks;
use PHPUnit\Framework\TestCase;

class ProcessorIfBlocksTest extends TestCase
{
    private ProcessorIfBlocks $processor;
    private string $viewsDir;

    protected function setUp(): void
    {
        // Crear prototipos de Or y And
        $orPrototype = new OrCondition();
        $andPrototype = new AndCondition();

        // Crear instancias de condiciones simples
        $notCondition = new NotCondition();
        $equalsCondition = new EqualsCondition();
        $notEqualsCondition = new NotEqualsCondition();
        $truthyCondition = new TruthyCondition();

        // Crear la fábrica de condiciones inyectando todas las dependencias
        $factory = new ConditionFactory(
            $orPrototype,
            $andPrototype,
            $notCondition,
            $equalsCondition,
            $notEqualsCondition,
            $truthyCondition
        );

        // Inyectar la fábrica en ProcessorIfBlocks
        $this->processor = new ProcessorIfBlocks($factory);

        // Ruta relativa a los templates
        $this->viewsDir = __DIR__ . '/../../resources/views/if/';
    }

    private function loadTemplate(string $filename): string
    {
        $path = $this->viewsDir . $filename;
        if (!file_exists($path)) {
            throw new \RuntimeException("Template file not found: $path");
        }
        return file_get_contents($path);
    }

    // --- Los tests existentes se mantienen igual ---
    public function testIfEquals()
    {
        $template = $this->loadTemplate('if_equals.html');

        $flatParams = ['theme' => 'active'];
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Active Theme', $result);

        $flatParams['theme'] = 'inactive';
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Inactive Theme', $result);
    }

    public function testIfNegation()
    {
        $template = $this->loadTemplate('if_negation.html');

        $flatParams = ['errors.username' => null];
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('No Errors', $result);

        $flatParams['errors.username'] = 'Required';
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Has Errors', $result);
    }

    public function testIfSimpleVar()
    {
        $template = $this->loadTemplate('if_simple.html');

        $flatParams = ['success' => true];
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Success!', $result);

        $flatParams['success'] = false;
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Failed!', $result);
    }

    // --- El resto de tests se mantiene igual ---
}
