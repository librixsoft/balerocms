<?php

namespace Tests\Framework\Rendering;

use Framework\Rendering\ProcessorIncludes;
use PHPUnit\Framework\TestCase;

class ProcessorIncludesTest extends TestCase
{
    private ProcessorIncludes $processor;
    private string $viewsDir;

    protected function setUp(): void
    {
        $this->processor = new ProcessorIncludes();
        $this->viewsDir = __DIR__ . '/../../resources/views/includes/';
    }

    private function loadTemplate(string $filename): string
    {
        $path = $this->viewsDir . $filename;
        if (!file_exists($path)) {
            throw new \RuntimeException("Template file not found: $path");
        }
        return file_get_contents($path);
    }

    public function testIncludeHeader()
    {
        $template = $this->loadTemplate('header.html');
        $result = $this->processor->process($template, $this->viewsDir);

        $this->assertStringContainsString('<header>HEADER CONTENT</header>', $result);
    }

    public function testIncludeFooter()
    {
        $template = $this->loadTemplate('footer.html');
        $result = $this->processor->process($template, $this->viewsDir);

        $this->assertStringContainsString('<footer>FOOTER CONTENT</footer>', $result);
    }

    public function testIncludeTemplateWithNestedIncludes()
    {
        $template = $this->loadTemplate('template_with_includes.html');
        $result = $this->processor->process($template, $this->viewsDir);

        $this->assertStringContainsString('<header>HEADER CONTENT</header>', $result);
        $this->assertStringContainsString('<main>', $result);
        $this->assertStringContainsString('Page Content', $result);
        $this->assertStringContainsString('</main>', $result);
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
        $template = $this->loadTemplate('header.html');
        $result = $this->processor->process($template, '');

        $this->assertStringContainsString('INCLUDE ERROR: baseDir no definido', $result);
    }
}
