<?php

use Framework\Rendering\ProcessorIncludes;
use PHPUnit\Framework\TestCase;

class ProcessorIncludesTest extends TestCase
{
    private ProcessorIncludes $processor;
    private string $viewsDir;

    public function testIncludeSingleFile()
    {
        $template = '<body><%-- @include "header.html" --></body>';
        $result = $this->processor->process($template, $this->viewsDir);

        $this->assertStringContainsString('<header>HEADER CONTENT</header>', $result);
        $this->assertStringNotContainsString('<%-- INCLUDE ERROR', $result);
    }

    public function testIncludeMultipleFiles()
    {
        $template = '<%-- @include "header.html" --><main>MAIN</main><%-- @include "footer.html" -->';
        $result = $this->processor->process($template, $this->viewsDir);

        $this->assertStringContainsString('<header>HEADER CONTENT</header>', $result);
        $this->assertStringContainsString('<footer>FOOTER CONTENT</footer>', $result);
    }

    public function testIncludeFileNotFound()
    {
        $template = '<%-- @include "notfound.html" -->';
        $result = $this->processor->process($template, $this->viewsDir);

        $this->assertStringContainsString('INCLUDE ERROR: Archivo no encontrado notfound.html', $result);
    }

    public function testIncludeWithoutBaseDir()
    {
        $template = '<%-- @include "header.html" -->';
        $result = $this->processor->process($template, '');

        $this->assertStringContainsString('INCLUDE ERROR: baseDir no definido', $result);
    }

    protected function setUp(): void
    {
        $this->processor = new ProcessorIncludes();
        $this->viewsDir = __DIR__ . '/../../resources/views/includes/';

        // Crear carpeta si no existe
        if (!is_dir($this->viewsDir)) {
            mkdir($this->viewsDir, 0777, true);
        }

        // Crear archivos de prueba
        file_put_contents($this->viewsDir . 'header.html', '<header>HEADER CONTENT</header>');
        file_put_contents($this->viewsDir . 'footer.html', '<footer>FOOTER CONTENT</footer>');
    }
}
