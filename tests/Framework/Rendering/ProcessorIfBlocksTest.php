<?php

namespace Tests\Framework\Rendering;

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

    public function testIfConcatenated()
    {
        $template = $this->loadTemplate('if_concatenated.html');

        $flatParams = ['theme' => 'active', 'mode' => 'dark'];
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Active Dark Mode', $result);

        $flatParams = ['theme' => 'inactive', 'mode' => 'dark'];
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Inactive or Light Mode', $result);

        $flatParams = ['theme' => 'active', 'mode' => 'light'];
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Inactive or Light Mode', $result);
    }

    public function testIfNested()
    {
        $template = $this->loadTemplate('if_nested.html');

        $flatParams = ['theme' => 'active', 'mode' => 'dark', 'installed' => 'yes'];
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Active Dark Theme', $result);
        $this->assertStringContainsString('Installed', $result);

        $flatParams['installed'] = 'no';
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Active Dark Theme', $result);
        $this->assertStringContainsString('Not Installed', $result);

        $flatParams = ['theme' => 'inactive', 'mode' => 'dark', 'installed' => 'yes'];
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Inactive Dark Theme', $result);
        $this->assertStringNotContainsString('Active Dark Theme', $result);
    }

    public function testIfNestedInnerWithAndOr()
    {
        $template = $this->loadTemplate('if_nested_inner_and_or.html');

        $flatParams = [
            'theme' => 'active',
            'mode' => 'dark',
            'installed' => 'yes',
            'version' => '1.0',
            'admin' => 'no'
        ];
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Active Dark Theme or Admin', $result);
        $this->assertStringContainsString('Installed or Version 2.0', $result);

        $flatParams['installed'] = 'no';
        $flatParams['version'] = '2.0';
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Active Dark Theme or Admin', $result);
        $this->assertStringContainsString('Installed or Version 2.0', $result);

        $flatParams['installed'] = 'no';
        $flatParams['version'] = '1.0';
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Active Dark Theme or Admin', $result);
        $this->assertStringContainsString('Not Installed and Not Version 2.0', $result);

        $flatParams = [
            'theme' => 'inactive',
            'mode' => 'light',
            'installed' => 'yes',
            'version' => '2.0',
            'admin' => 'yes'
        ];
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Active Dark Theme or Admin', $result);
        $this->assertStringContainsString('Installed or Version 2.0', $result);

        $flatParams = [
            'theme' => 'inactive',
            'mode' => 'light',
            'installed' => 'no',
            'version' => '1.0',
            'admin' => 'no'
        ];
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Inactive Dark Theme and Not Admin', $result);
        $this->assertStringNotContainsString('Installed or Version 2.0', $result);
        $this->assertStringNotContainsString('Not Installed and Not Version 2.0', $result);
    }

    public function testFiveNestedIfs()
    {
        $template = $this->loadTemplate('if_5_nested.html');

        $flatParams = [
            'theme' => 'active',
            'mode' => 'dark',
            'admin' => 'yes',
            'installed' => 'yes',
            'version' => '2.0',
            'beta' => 'no',
            'premium' => 'yes'
        ];
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Level 1: Active Dark Theme', $result);
        $this->assertStringContainsString('Level 2: Admin Access', $result);
        $this->assertStringContainsString('Level 3: Installed or Version 2.0', $result);
        $this->assertStringContainsString('Level 4: Not Beta', $result);
        $this->assertStringContainsString('Level 5: Premium User', $result);

        $flatParams['beta'] = 'yes';
        $flatParams['premium'] = 'yes';
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Level 4: Beta User', $result);
        $this->assertStringNotContainsString('Level 5: Premium User', $result);
        $this->assertStringNotContainsString('Level 5: Regular User', $result);

        $flatParams['beta'] = 'no';
        $flatParams['premium'] = 'no';
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Level 5: Regular User', $result);

        $flatParams = [
            'theme' => 'active',
            'mode' => 'dark',
            'admin' => 'no',
            'installed' => 'yes',
            'version' => '2.0',
            'beta' => 'no',
            'premium' => 'yes'
        ];
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Level 2: No Admin Access', $result);
        $this->assertStringNotContainsString('Level 3:', $result);
        $this->assertStringNotContainsString('Level 4:', $result);
        $this->assertStringNotContainsString('Level 5:', $result);

        $flatParams['theme'] = 'inactive';
        $flatParams['admin'] = 'yes';
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Level 1: Inactive Theme', $result);
        $this->assertStringNotContainsString('Level 2:', $result);
        $this->assertStringNotContainsString('Level 3:', $result);
        $this->assertStringNotContainsString('Level 4:', $result);
        $this->assertStringNotContainsString('Level 5:', $result);
    }

    public function testIfNotEquals()
    {
        $template = $this->loadTemplate('if_not_equals.html');

        $flatParams = ['theme' => 'inactive'];
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Inactive Theme', $result);
        $this->assertStringNotContainsString('Active Theme', $result);

        $flatParams = ['theme' => 'active'];
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Active Theme', $result);
        $this->assertStringNotContainsString('Inactive Theme', $result);

        $flatParams = ['theme' => null];
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Inactive Theme', $result);
        $this->assertStringNotContainsString('Active Theme', $result);

        $flatParams = [];
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Inactive Theme', $result);
        $this->assertStringNotContainsString('Active Theme', $result);
    }
}
