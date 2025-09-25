<?php

namespace Tests\Framework\Rendering;

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

    private function loadTemplate(string $filename): string
    {
        $path = $this->viewsDir . $filename;
        if (!file_exists($path)) {
            throw new \RuntimeException("Template file not found: $path");
        }
        return file_get_contents($path);
    }

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

    public function testForeachWithIf()
    {
        $template = $this->loadTemplate('foreach_with_if.html');

        $params = [
            'themes' => [
                ['name' => 'Light', 'value' => 'light'],
                ['name' => 'Dark', 'value' => 'dark'],
            ],
            'defaultTheme' => 'dark'
        ];

        $result = $this->processorForEach->process($template, $params);
        $resultNormalized = preg_replace('/\s+/', ' ', $result);

        $this->assertStringContainsString('Theme: Light, Value: light (Not Default)', $resultNormalized);
        $this->assertStringContainsString('Theme: Dark, Value: dark (Default)', $resultNormalized);
    }

    public function testForeachWithIfNested()
    {
        $template = $this->loadTemplate('foreach_with_if_nested.html');

        $params = [
            'themes' => [
                ['name' => 'Blue', 'value' => 'blue'],
                ['name' => 'Dark', 'value' => 'dark'],
                ['name' => 'Light', 'value' => 'light'],
            ],
            'defaultTheme' => 'dark'
        ];

        $result = $this->processorForEach->process($template, $params);
        $resultNormalized = preg_replace('/\s+/', ' ', $result);

        $this->assertStringContainsString('Theme: Blue, Value: blue', $resultNormalized);
        $this->assertStringContainsString('Theme: Dark, Value: dark', $resultNormalized);
        $this->assertStringContainsString('Theme: Light, Value: light', $resultNormalized);

        $this->assertStringContainsString('(Not Default)', $resultNormalized);
        $this->assertStringContainsString('(Default)', $resultNormalized);

        $this->assertStringContainsString('- Dark Mode Active', $resultNormalized);
        $this->assertStringContainsString('- Light Mode', $resultNormalized);
    }

    public function testForeachWithIfNestedAndOr()
    {
        $template = $this->loadTemplate('foreach_with_if_nested_and_or.html');

        $params = [
            'themes' => [
                ['name' => 'Blue', 'value' => 'blue'],
                ['name' => 'Dark', 'value' => 'dark'],
            ],
            'defaultTheme' => 'dark',
            'theme' => 'active',
            'mode' => 'dark',
            'admin' => 'yes',
            'installed' => 'yes',
            'version' => '2.0',
            'beta' => 'no',
            'premium' => 'no',
        ];

        $result = $this->processorForEach->process($template, $params);
        $resultNormalized = preg_replace('/\s+/', ' ', $result);

        $this->assertStringContainsString('Theme: Blue, Value: blue', $resultNormalized);
        $this->assertStringContainsString('Theme: Dark, Value: dark', $resultNormalized);

        $this->assertStringContainsString('(Not Default)', $resultNormalized);
        $this->assertStringContainsString('(Default)', $resultNormalized);

        $this->assertStringContainsString('- Dark Mode Active', $resultNormalized);
        $this->assertStringContainsString('- Light Mode', $resultNormalized);

        $this->assertStringContainsString('Level 1: Active Dark Theme', $resultNormalized);
        $this->assertStringContainsString('Level 2: Admin Access', $resultNormalized);
        $this->assertStringContainsString('Level 3: Installed or Version 2.0', $resultNormalized);
        $this->assertStringContainsString('Level 4: Not Beta', $resultNormalized);
        $this->assertStringContainsString('Level 5: Regular User', $resultNormalized);
    }

    public function testForeachKeyValue()
    {
        $template = $this->loadTemplate('foreach_key_value.html');

        $params = [
            'errors' => [
                'username' => 'El nombre de usuario no puede estar vacío.',
                'passwd' => 'La contraseña no puede estar vacía.',
                'email' => 'El correo electrónico no es válido.'
            ]
        ];

        $result = $this->processorForEach->process($template, $params);
        $resultNormalized = preg_replace('/\s+/', ' ', $result);

        $this->assertStringContainsString("Error en username: El nombre de usuario no puede estar vacío.", $resultNormalized);
        $this->assertStringContainsString("Error en passwd: La contraseña no puede estar vacía.", $resultNormalized);
        $this->assertStringContainsString("Error en email: El correo electrónico no es válido.", $resultNormalized);
    }
}
