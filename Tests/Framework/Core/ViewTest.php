<?php

namespace Tests\Framework\Core;

use Framework\Core\View;
use Framework\Core\ConfigSettings;
use Framework\Config\ViewConfig;
use Framework\Rendering\TemplateEngine;
use Framework\Rendering\ProcessorFlattenParams;
use Framework\Rendering\ProcessorForEach;
use Framework\Rendering\ProcessorIfBlocks;
use Framework\Rendering\ProcessorIncludes;
use Framework\Rendering\ProcessorKeyPath;
use Framework\Rendering\ProcessorVariables;
use Framework\Rendering\ProcessorTernary;
use Framework\I18n\LangManager;
use Framework\Exceptions\ViewException;
use Framework\Preview\PreviewGenerator;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

#[CoversClass(View::class)]
#[TestDox("View class with mocked TemplateEngine and temporary template")]
class ViewTest extends TestCase
{
    private string $tempDir;
    private View $view;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/view_test_' . uniqid();
        mkdir($this->tempDir . '/themes/default', 0777, true);
        mkdir($this->tempDir . '/lang', 0777, true);

        file_put_contents($this->tempDir . '/themes/default/test.html', 'Hello {title}');

        $configSettings = $this->createMock(ConfigSettings::class);
        $configSettings->title = 'Test Title';
        $configSettings->url = 'https://example.com';
        $configSettings->keywords = 'test,php';
        $configSettings->description = 'Test Description';
        $configSettings->basepath = '/';
        $configSettings->footer = 'Test Footer';
        $configSettings->theme = 'default';
        $configSettings->method('loadSettings')->willReturnCallback(fn() => null);

        $langManager = $this->createMock(LangManager::class);
        $langManager->method('setCurrentLang')->willReturnCallback(fn() => null);
        $langManager->method('load')->willReturnCallback(fn() => null);
        $langManager->method('get')->willReturnCallback(fn($key, $default) => $default);

        $previewGenerator = $this->createMock(PreviewGenerator::class);
        $previewGenerator->method('generatePreviewUrl')->willReturn('https://example.com/preview.png');

        $templateEngine = $this->createTemplateEngineWithMocks('Test Title');

        $viewConfig = new ViewConfig($this->tempDir, $this->tempDir . '/lang', ['html']);

        $this->view = new View(
            $configSettings,
            $templateEngine,
            $langManager,
            $viewConfig,
            $previewGenerator
        );
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->tempDir);
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    private function createTemplateEngineWithMocks(string $title): TemplateEngine
    {
        $processorIncludes = $this->createMock(ProcessorIncludes::class);
        $processorFlattenParams = $this->createMock(ProcessorFlattenParams::class);
        $processorForEach = $this->createMock(ProcessorForEach::class);
        $processorIfBlocks = $this->createMock(ProcessorIfBlocks::class);
        $processorVariables = $this->createMock(ProcessorVariables::class);
        $processorKeyPath = $this->createMock(ProcessorKeyPath::class);
        $processorTernary = $this->createMock(ProcessorTernary::class);

        $processorIncludes->method('process')->willReturnCallback(fn($content, $baseDir) => $content);
        $processorFlattenParams->method('process')->willReturnCallback(fn($params) => $params);
        $processorForEach->method('process')->willReturnCallback(fn($content, $params) => $content);
        $processorTernary->method('process')->willReturnCallback(fn($content, $flatParams) => $content);
        $processorVariables->method('process')->willReturnCallback(
            fn($content, $flatParams) => strtr($content, ['{title}' => $title])
        );
        $processorIfBlocks->method('process')->willReturnCallback(fn($content, $flatParams) => $content);
        $processorKeyPath->method('process')->willReturnCallback(fn($content, $flatParams) => $content);

        $engine = new TemplateEngine(
            $processorIncludes,
            $processorFlattenParams,
            $processorForEach,
            $processorIfBlocks,
            $processorVariables,
            $processorKeyPath,
            $processorTernary
        );

        $engine->setBaseDir($this->tempDir);
        return $engine;
    }

    #[Test]
    #[TestDox("Render a simple template correctly")]
    public function testRenderSimpleTemplate(): void
    {
        $output = $this->view->render('test', []);
        $this->assertStringContainsString('Hello Test Title', $output);
    }

    #[Test]
    #[TestDox("Throws exception for invalid template extension")]
    public function testRenderInvalidExtension(): void
    {
        file_put_contents($this->tempDir . '/themes/default/invalid.txt', 'Invalid template');
        $this->expectException(ViewException::class);
        $this->view->render('invalid.txt', []);
    }
}