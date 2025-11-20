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

    public function testIfArrayExists()
    {
        $template = $this->loadTemplate('if_array_exists.html');

        // Caso 1: El array/objeto "array_page" existe (tiene al menos una propiedad)
        $flatParams = [
            'array_page.virtual_title' => 'Welcome Page',
            'array_page.virtual_content' => 'This is the content'
        ];
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Page array or object exists!', $result);
        $this->assertStringContainsString('Virtual Title:', $result);
        $this->assertStringContainsString('{page.virtual_title}', $result); // El placeholder aún no se reemplaza
        $this->assertStringNotContainsString('No page found.', $result);

        // Caso 2: El array/objeto "array_page" NO existe
        $flatParams = [];
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('No page found.', $result);
        $this->assertStringNotContainsString('Page array or object exists!', $result);

        // Caso 3: Existen otras variables pero NO "array_page"
        $flatParams = [
            'other.title' => 'Other Title',
            'user.name' => 'John'
        ];
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('No page found.', $result);
        $this->assertStringNotContainsString('Page array or object exists!', $result);
    }

    public function testIfElseIf()
    {
        $template = $this->loadTemplate('if_elseif.html');

        // Caso 1: blocks existe - debe mostrar solo "Blocks exist"
        $flatParams = [
            'blocks.0.content' => 'Block content 1',
            'blocks.1.content' => 'Block content 2'
        ];
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Blocks exist', $result);
        $this->assertStringNotContainsString('No content available', $result);
        $this->assertStringNotContainsString('{page.virtual_title}', $result);

        // Caso 2: blocks NO existe pero page SÍ existe - debe mostrar solo page
        $flatParams = [
            'page.virtual_title' => 'Welcome Page',
            'page.virtual_content' => 'This is the page content'
        ];
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('{page.virtual_title}', $result);
        $this->assertStringContainsString('{page.virtual_content}', $result);
        $this->assertStringNotContainsString('No content available', $result);
        $this->assertStringNotContainsString('Blocks exist', $result);

        // Caso 3: NI blocks NI page existen - debe mostrar solo "No content available"
        $flatParams = [];
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('No content available', $result);
        $this->assertStringNotContainsString('{page.virtual_title}', $result);
        $this->assertStringNotContainsString('Blocks exist', $result);

        // Caso 4: Ambos existen - debe mostrar solo blocks (primera condición verdadera)
        $flatParams = [
            'blocks.0.content' => 'Block content',
            'page.virtual_title' => 'Page Title'
        ];
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Blocks exist', $result);
        $this->assertStringNotContainsString('{page.virtual_title}', $result);
        $this->assertStringNotContainsString('No content available', $result);
    }

    // Agregar estos métodos a tu clase ProcessorIfBlocksTest

    public function testIfWithPipesOperator()
    {
        $template = $this->loadTemplate('if_pipes_operator.html');

        // Primera condición true
        $flatParams = ['mod_id' => 'block_new'];
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Form Mode: New or Edit', $result);

        // Segunda condición true
        $flatParams = ['mod_id' => 'block_edit'];
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Form Mode: New or Edit', $result);

        // Ninguna condición true
        $flatParams = ['mod_id' => 'all_blocks'];
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('List Mode', $result);
        $this->assertStringNotContainsString('Form Mode: New or Edit', $result);
    }

    public function testIfWithAmpersandOperator()
    {
        $template = $this->loadTemplate('if_ampersand_operator.html');

        // Ambas condiciones true
        $flatParams = ['theme' => 'active', 'mode' => 'dark'];
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Active Dark Mode', $result);

        // Primera true, segunda false
        $flatParams = ['theme' => 'active', 'mode' => 'light'];
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Not Active Dark', $result);
        $this->assertStringNotContainsString('Active Dark Mode', $result);

        // Ambas false
        $flatParams = ['theme' => 'inactive', 'mode' => 'light'];
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Not Active Dark', $result);
        $this->assertStringNotContainsString('Active Dark Mode', $result);
    }

    public function testIfMixedOperators()
    {
        $template = $this->loadTemplate('if_mixed_operators.html');

        // Expresión: mod_id == "block_new" || (mod_id == "block_edit" && theme == "active" && mode == "dark")
        // Sin paréntesis, AND tiene precedencia sobre OR

        // Caso 1: mod_id == "block_new" (primera parte del OR es true)
        $flatParams = [
            'mod_id' => 'block_new',
            'theme' => 'inactive',
            'mode' => 'light'
        ];
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Edit Mode with Active Dark', $result);

        // Caso 2: mod_id == "block_edit" && theme == "active" && mode == "dark" (segunda parte del OR es true)
        $flatParams = [
            'mod_id' => 'block_edit',
            'theme' => 'active',
            'mode' => 'dark'
        ];
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Edit Mode with Active Dark', $result);

        // Caso 3: Ninguna parte es true
        $flatParams = [
            'mod_id' => 'block_edit',
            'theme' => 'inactive',
            'mode' => 'light'
        ];
        $result = $this->processor->process($template, $flatParams);
        $this->assertStringContainsString('Other Mode', $result);
        $this->assertStringNotContainsString('Edit Mode with Active Dark', $result);
    }
}
