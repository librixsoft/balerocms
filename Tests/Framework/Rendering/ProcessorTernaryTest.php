<?php

namespace Tests\Framework\Rendering;

use Framework\Rendering\ProcessorTernary;
use PHPUnit\Framework\TestCase;

class ProcessorTernaryTest extends TestCase
{
    private ProcessorTernary $processor;
    private string $viewsDir;

    protected function setUp(): void
    {
        $this->processor = new ProcessorTernary();
        $this->viewsDir = __DIR__ . '/../../resources/views/ternary/';
    }

    private function loadTemplate(string $filename): string
    {
        $path = $this->viewsDir . $filename;
        if (!file_exists($path)) {
            throw new \RuntimeException("Template file not found: $path");
        }
        return file_get_contents($path);
    }

    public function testTernaryEquals()
    {
        $template = $this->loadTemplate('ternary_equals.html');

        $params = ['activeMenu' => 'settings'];
        $result = $this->processor->process($template, $params);
        $this->assertStringContainsString('class="nav-link active"', $result);

        $params = ['activeMenu' => 'pages'];
        $result = $this->processor->process($template, $params);
        $this->assertStringContainsString('class="nav-link "', $result);
    }

    public function testTernaryNotEquals()
    {
        $template = $this->loadTemplate('ternary_not_equals.html');

        $params = ['status' => 'active'];
        $result = $this->processor->process($template, $params);
        $this->assertStringContainsString('class="enabled"', $result);

        $params = ['status' => 'inactive'];
        $result = $this->processor->process($template, $params);
        $this->assertStringContainsString('class="disabled"', $result);
    }

    public function testTernaryStrictEquals()
    {
        $template = $this->loadTemplate('ternary_strict_equals.html');

        $params = ['count' => '0'];
        $result = $this->processor->process($template, $params);
        $this->assertStringContainsString('class="zero"', $result);

        $params = ['count' => 0];
        $result = $this->processor->process($template, $params);
        $this->assertStringContainsString('class="non-zero"', $result);
    }

    public function testTernaryStrictNotEquals()
    {
        $template = $this->loadTemplate('ternary_strict_not_equals.html');

        $params = ['value' => 'false'];
        $result = $this->processor->process($template, $params);
        $this->assertStringContainsString('class="falsy"', $result);

        $params = ['value' => false];
        $result = $this->processor->process($template, $params);
        $this->assertStringContainsString('class="truthy"', $result);
    }

    public function testMultipleTernaries()
    {
        $template = $this->loadTemplate('ternary_multiple.html');

        $params = ['activeMenu' => 'pages'];
        $result = $this->processor->process($template, $params);

        $this->assertStringContainsString('<a class="nav-link ">Settings</a>', $result);
        $this->assertStringContainsString('<a class="nav-link active">Pages</a>', $result);
        $this->assertStringContainsString('<a class="nav-link ">Blocks</a>', $result);
    }

    public function testTernaryWithNonExistentVariable()
    {
        $template = $this->loadTemplate('ternary_non_existent.html');

        $params = [];
        $result = $this->processor->process($template, $params);
        $this->assertStringContainsString('class="no"', $result);
    }

    public function testTernaryWithEmptyStrings()
    {
        $template = $this->loadTemplate('ternary_empty_string.html');

        $params = ['text' => ''];
        $result = $this->processor->process($template, $params);
        $this->assertStringContainsString('class="empty"', $result);

        $params = ['text' => 'hello'];
        $result = $this->processor->process($template, $params);
        $this->assertStringContainsString('class="filled"', $result);
    }

    public function testTernaryWithSpecialCharacters()
    {
        $template = $this->loadTemplate('ternary_special_chars.html');

        $params = ['status' => 'success'];
        $result = $this->processor->process($template, $params);
        $this->assertStringContainsString('data-status="✓"', $result);

        $params = ['status' => 'failed'];
        $result = $this->processor->process($template, $params);
        $this->assertStringContainsString('data-status="✗"', $result);
    }

    public function testTernaryWithNumbers()
    {
        $template = $this->loadTemplate('ternary_numbers.html');

        $params = ['count' => '0'];
        $result = $this->processor->process($template, $params);
        $this->assertStringContainsString('class="badge-empty"', $result);

        $params = ['count' => '5'];
        $result = $this->processor->process($template, $params);
        $this->assertStringContainsString('class="badge-count"', $result);
    }

    public function testTernaryInComplexHTML()
    {
        $template = $this->loadTemplate('ternary_complex.html');

        $params = [
            'theme' => 'dark',
            'activeMenu' => 'about'
        ];
        $result = $this->processor->process($template, $params);

        $this->assertStringContainsString('class="navbar navbar-dark"', $result);
        $this->assertStringContainsString('<li class="">', $result);
        $this->assertStringContainsString('<li class="active">', $result);
    }

    public function testTernaryDoesNotAffectOtherPlaceholders()
    {
        $template = $this->loadTemplate('ternary_with_placeholders.html');

        $params = [
            'activeMenu' => 'home',
            'title' => 'Welcome',
            'description' => 'Site description'
        ];

        $result = $this->processor->process($template, $params);

        $this->assertStringContainsString('class="active"', $result);
        $this->assertStringContainsString('{title}', $result);
        $this->assertStringContainsString('{description}', $result);
    }

    public function testTernaryWithNestedVariables()
    {
        $template = $this->loadTemplate('ternary_nested_variables.html');

        // Test case 1: mod_id == 'block_new'
        $params = [
            'mod_id' => 'block_new',
            'admin' => [
                'create' => 'Create Block',
                'save' => 'Save Changes'
            ]
        ];
        $result = $this->processor->process($template, $params);
        $this->assertStringContainsString('<i class="fas fa-plus"></i>', $result);
        $this->assertStringContainsString('Create Block', $result);

        // Test case 2: mod_id == 'block_edit'
        $params = [
            'mod_id' => 'block_edit',
            'admin' => [
                'create' => 'Create Block',
                'save' => 'Save Changes'
            ]
        ];
        $result = $this->processor->process($template, $params);
        $this->assertStringContainsString('<i class="fas fa-save"></i>', $result);
        $this->assertStringContainsString('Save Changes', $result);
    }

    public function testTernaryPreservesTranslationKeys()
    {
        $template = $this->loadTemplate('ternary_translation_keys.html');

        // Solo pasamos mod_id, NO pasamos el array 'admin'
        // Esto simula que admin.create y admin.save son claves de traducción
        $params = ['mod_id' => 'block_new'];

        $result = $this->processor->process($template, $params);

        // Debe preservar las llaves para que el procesador de traducciones lo maneje
        $this->assertEquals('{admin.create}', trim($result));

        // Test case 2: cuando la condición es falsa
        $params['mod_id'] = 'block_edit';
        $result = $this->processor->process($template, $params);
        $this->assertEquals('{admin.save}', trim($result));
    }
}