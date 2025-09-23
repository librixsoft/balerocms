<?php

use Framework\Rendering\Conditions\AndCondition;
use Framework\Rendering\Conditions\ConditionFactory;
use Framework\Rendering\Conditions\OrCondition;
use Framework\Rendering\ProcessorIfBlocks;
use PHPUnit\Framework\TestCase;

class ProcessorIfBlocksTest extends TestCase
{
    private ProcessorIfBlocks $processor;
    private string $viewsDir;

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

    private function loadTemplate(string $filename): string
    {
        $path = $this->viewsDir . $filename;
        if (!file_exists($path)) {
            throw new \RuntimeException("Template file not found: $path");
        }
        return file_get_contents($path);
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

        // Caso: theme active, mode dark, installed yes
        $flatParams = ['theme' => 'active', 'mode' => 'dark', 'installed' => 'yes'];
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Active Dark Theme', $result);
        $this->assertStringContainsString('Installed', $result);

        // Caso: theme active, mode dark, installed no
        $flatParams['installed'] = 'no';
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Active Dark Theme', $result);
        $this->assertStringContainsString('Not Installed', $result);

        // Caso: theme inactive
        $flatParams = ['theme' => 'inactive', 'mode' => 'dark', 'installed' => 'yes'];
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Inactive Dark Theme', $result);
        $this->assertStringNotContainsString('Active Dark Theme', $result);
    }

    public function testIfNestedInnerWithAndOr()
    {
        $template = $this->loadTemplate('if_nested_inner_and_or.html');

        // Caso 1: theme active, mode dark, installed yes
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

        // Caso 2: theme active, mode dark, installed no, version 2.0
        $flatParams['installed'] = 'no';
        $flatParams['version'] = '2.0';
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Active Dark Theme or Admin', $result);
        $this->assertStringContainsString('Installed or Version 2.0', $result);

        // Caso 3: theme active, mode dark, installed no, version 1.0
        $flatParams['installed'] = 'no';
        $flatParams['version'] = '1.0';
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Active Dark Theme or Admin', $result);
        $this->assertStringContainsString('Not Installed and Not Version 2.0', $result);

        // Caso 4: theme inactive, admin yes
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

        // Caso 5: theme inactive, admin no
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

        // Caso 1: Todos cumplen para niveles 1-5
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

        // Caso 2: beta = yes → Nivel 4 cambia a Beta User, Nivel 5 no se ejecuta
        $flatParams['beta'] = 'yes';
        $flatParams['premium'] = 'yes';
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Level 4: Beta User', $result);
        $this->assertStringNotContainsString('Level 5: Premium User', $result);
        $this->assertStringNotContainsString('Level 5: Regular User', $result);

        // Caso 3: beta = no, premium = no → Nivel 5 Regular User
        $flatParams['beta'] = 'no';
        $flatParams['premium'] = 'no';
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Level 5: Regular User', $result);

        // Caso 4: admin = no → Nivel 2 cambia a No Admin Access, bloques internos no se ejecutan
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

        // Caso 5: theme inactive → Nivel 1 Inactive Theme
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

        // Caso 1: theme = inactive → bloque != 'active' debe ser true
        $flatParams = ['theme' => 'inactive'];
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Inactive Theme', $result);
        $this->assertStringNotContainsString('Active Theme', $result);

        // Caso 2: theme = active → bloque != 'active' debe ser false
        $flatParams = ['theme' => 'active'];
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Active Theme', $result);
        $this->assertStringNotContainsString('Inactive Theme', $result);

        // Caso 3: theme = null → != 'active' debe ser true
        $flatParams = ['theme' => null];
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Inactive Theme', $result);
        $this->assertStringNotContainsString('Active Theme', $result);

        // Caso 4: theme no definido → != 'active' debe ser true
        $flatParams = [];
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Inactive Theme', $result);
        $this->assertStringNotContainsString('Active Theme', $result);
    }

    protected function setUp(): void
    {
        // Crear prototipos de Or y And
        $orPrototype = new OrCondition();
        $andPrototype = new AndCondition();

        // Crear la fábrica con prototipos inyectados
        $factory = new ConditionFactory($orPrototype, $andPrototype);

        // Inyectar la fábrica en ProcessorIfBlocks
        $this->processor = new ProcessorIfBlocks($factory);

        // Ruta relativa a los templates
        $this->viewsDir = __DIR__ . '/../../resources/views/if/';
    }


}
