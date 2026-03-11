<?php

declare(strict_types=1);

namespace Tests\Framework\Core;

use Exception;
use Framework\Core\EarlyErrorConsole;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

/**
 * Tests para EarlyErrorConsole.
 *
 * Estrategia:
 *  - Usamos un partial mock para evitar que haltExecution() llame a exit,
 *    permitiendo verificar el resto del flujo de render() sin abortar el proceso.
 *  - Los métodos protected (getHtmlTemplate, generateTraceHtml, getCss) se
 *    prueban de forma directa vía Reflection para máxima cobertura.
 */
final class EarlyErrorConsoleTest extends TestCase
{
    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Devuelve un mock parcial de EarlyErrorConsole donde haltExecution()
     * no ejecuta exit real.
     */
    private function makeConsole(): EarlyErrorConsole
    {
        return $this->getMockBuilder(EarlyErrorConsole::class)
            ->onlyMethods(['haltExecution'])
            ->getMock();
    }

    /**
     * Llama a un método protected/private en el objeto dado vía Reflection.
     */
    private function callProtected(object $obj, string $method, array $args = []): mixed
    {
        $ref = new \ReflectionMethod($obj, $method);
        $ref->setAccessible(true);
        return $ref->invokeArgs($obj, $args);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // render()
    // ─────────────────────────────────────────────────────────────────────────

    public function testRenderOutputsHtmlWithErrorClass(): void
    {
        $console = $this->makeConsole();
        $console->expects($this->once())->method('haltExecution');

        $exception = new RuntimeException('Test error message');

        ob_start();
        $console->render($exception);
        $output = ob_get_clean();

        $this->assertStringContainsString('RuntimeException', $output);
        $this->assertStringContainsString('Test error message', $output);
        $this->assertStringContainsString('Early Bootstrap Error', $output);
    }

    public function testRenderOutputsFileAndLineLocation(): void
    {
        $console   = $this->makeConsole();
        $console->expects($this->once())->method('haltExecution');
        $exception = new RuntimeException('Location test');

        ob_start();
        $console->render($exception);
        $output = ob_get_clean();

        $this->assertStringContainsString('File:', $output);
        $this->assertStringContainsString('Line:', $output);
    }

    public function testRenderCleansOutputBuffer(): void
    {
        $console = $this->makeConsole();
        $console->expects($this->once())->method('haltExecution');

        // Abrimos un buffer con contenido basura
        ob_start();
        echo 'stale-output';

        ob_start();
        $console->render(new Exception('clean buffer'));
        $output = ob_get_clean();
        ob_end_clean(); // cierra el buffer externo que ya fue limpiado

        // El stale content fue limpiado por ob_clean dentro de render()
        $this->assertStringNotContainsString('stale-output', $output);
    }

    public function testRenderCallsHaltExecution(): void
    {
        $console = $this->makeConsole();
        $console->expects($this->once())->method('haltExecution');

        ob_start();
        $console->render(new Exception('halt test'));
        ob_end_clean();
    }

    public function testRenderEscapesSpecialCharsInMessage(): void
    {
        $console   = $this->makeConsole();
        $console->expects($this->once())->method('haltExecution');
        $exception = new RuntimeException('<b>XSS</b> & attack "here"');

        ob_start();
        $console->render($exception);
        $output = ob_get_clean();

        // El mensaje debe estar escapado
        $this->assertStringContainsString('&lt;b&gt;XSS&lt;/b&gt;', $output);
        $this->assertStringNotContainsString('<b>XSS</b>', $output);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // getHtmlTemplate()
    // ─────────────────────────────────────────────────────────────────────────

    public function testGetHtmlTemplateContainsDoctype(): void
    {
        $console   = new EarlyErrorConsole();
        $exception = new Exception('template test');

        ob_start();
        $this->callProtected($console, 'getHtmlTemplate', [
            'My Message', 'My Detail', 'My Location', $exception,
        ]);
        $output = ob_get_clean();

        $this->assertStringContainsString('<!DOCTYPE html>', $output);
    }

    public function testGetHtmlTemplateContainsAllSections(): void
    {
        $console   = new EarlyErrorConsole();
        $exception = new Exception('sections test');

        ob_start();
        $this->callProtected($console, 'getHtmlTemplate', [
            'Section Message', 'Section Detail', 'Section Location', $exception,
        ]);
        $output = ob_get_clean();

        $this->assertStringContainsString('Section Message', $output);
        $this->assertStringContainsString('Section Detail', $output);
        $this->assertStringContainsString('Section Location', $output);
        $this->assertStringContainsString('<style>', $output);
        $this->assertStringContainsString('console-title', $output);
        $this->assertStringContainsString('console-body', $output);
        $this->assertStringContainsString('</html>', $output);
    }

    public function testGetHtmlTemplateEmbedsCss(): void
    {
        $console   = new EarlyErrorConsole();
        $exception = new Exception('css embed');

        ob_start();
        $this->callProtected($console, 'getHtmlTemplate', [
            'M', 'D', 'L', $exception,
        ]);
        $output = ob_get_clean();

        // El CSS siempre incluye la regla del body
        $this->assertStringContainsString('background: #1a1a1a', $output);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // generateTraceHtml()
    // ─────────────────────────────────────────────────────────────────────────

    public function testGenerateTraceHtmlReturnsTraceDiv(): void
    {
        $console = new EarlyErrorConsole();

        $html = $this->callProtected($console, 'generateTraceHtml', [[]]);

        $this->assertStringContainsString('<div class="trace">', $html);
        $this->assertStringContainsString('</div>', $html);
    }

    public function testGenerateTraceHtmlContainsTraceItems(): void
    {
        $console   = new EarlyErrorConsole();
        $exception = new \Exception('trace test');

        // Ahora generateTraceHtml recibe el array de trace directamente
        $html = $this->callProtected($console, 'generateTraceHtml', [$exception->getTrace()]);

        $this->assertStringContainsString('trace-item', $html);
        $this->assertStringContainsString('<code>', $html);
    }

    public function testGenerateTraceHtmlHandlesMissingFileGracefully(): void
    {
        $console = new EarlyErrorConsole();
        $trace   = [
            ['function' => 'someFunc', 'line' => 42],   // sin 'file'
            ['function' => 'anotherFunc'],               // sin 'file' ni 'line'
            [],                                          // entrada vacía
        ];

        $html = $this->callProtected($console, 'generateTraceHtml', [$trace]);

        $this->assertStringContainsString('[internal]', $html);
        $this->assertStringContainsString('someFunc()', $html);
        $this->assertStringContainsString('anotherFunc()', $html);
        $this->assertStringContainsString('?', $html);
    }

    public function testGenerateTraceHtmlEscapesFunctionName(): void
    {
        $console = new EarlyErrorConsole();
        $trace   = [
            ['function' => '<script>xss</script>', 'file' => '/tmp/x.php', 'line' => 1],
        ];

        $html = $this->callProtected($console, 'generateTraceHtml', [$trace]);

        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>xss', $html);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // getCss()
    // ─────────────────────────────────────────────────────────────────────────

    public function testGetCssReturnsNonEmptyString(): void
    {
        $console = new EarlyErrorConsole();
        $css     = $this->callProtected($console, 'getCss', []);

        $this->assertIsString($css);
        $this->assertNotEmpty($css);
    }

    public function testGetCssContainsCriticalRules(): void
    {
        $console = new EarlyErrorConsole();
        $css     = $this->callProtected($console, 'getCss', []);

        $this->assertStringContainsString('background: #1a1a1a', $css);
        $this->assertStringContainsString('.console', $css);
        $this->assertStringContainsString('.trace', $css);
        $this->assertStringContainsString('.warning', $css);
        $this->assertStringContainsString('::selection', $css);
    }

    public function testGetCssIsTheSameOnMultipleCalls(): void
    {
        $console = new EarlyErrorConsole();
        $css1    = $this->callProtected($console, 'getCss', []);
        $css2    = $this->callProtected($console, 'getCss', []);

        $this->assertSame($css1, $css2);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Reflection / API surface
    // ─────────────────────────────────────────────────────────────────────────

    public function testClassHasExpectedPublicMethods(): void
    {
        $r = new \ReflectionClass(EarlyErrorConsole::class);

        $this->assertTrue($r->hasMethod('render'));
        $this->assertTrue($r->getMethod('render')->isPublic());
    }

    public function testClassHasExpectedProtectedMethods(): void
    {
        $r = new \ReflectionClass(EarlyErrorConsole::class);

        foreach (['haltExecution', 'getHtmlTemplate', 'generateTraceHtml', 'getCss'] as $method) {
            $this->assertTrue($r->hasMethod($method), "Method $method not found");
            $this->assertTrue($r->getMethod($method)->isProtected(), "Method $method should be protected");
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers internos del test
    // ─────────────────────────────────────────────────────────────────────────
}
