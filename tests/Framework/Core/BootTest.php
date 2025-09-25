<?php

namespace Tests\Framework\Core;

use Framework\Core\Boot;
use Framework\Core\Container;
use Framework\Config\Context;
use PHPUnit\Framework\TestCase;

final class BootTest extends TestCase
{
    private string $configDir;
    private string $configFile;

    protected function setUp(): void
    {
        // Definir LOCAL_DIR si no está definido
        if (!defined('LOCAL_DIR')) {
            define('LOCAL_DIR', sys_get_temp_dir());
        }

        // Crear carpeta resources/config en el directorio temporal
        $this->configDir = LOCAL_DIR . '/resources/config';
        if (!is_dir($this->configDir)) {
            mkdir($this->configDir, 0777, true);
        }

        // Archivo de configuración requerido por Context/ConfigSettings
        $this->configFile = $this->configDir . '/balero.config.json';

        $jsonContent = [
            'config' => [
                'database' => [
                    'dbhost' => 'localhost',
                    'dbuser' => 'root',
                    'dbpass' => '1234',
                    'dbname' => 'cms',
                ],
                'admin' => [
                    'username' => 'admin',
                    'pass' => 'admin123',
                    'email' => 'admin@test.com',
                    'firstname' => 'Anibal',
                    'lastname' => 'Gomez',
                ],
                'system' => ['installed' => '1'],
                'site' => [
                    'title' => 'Test CMS',
                    'description' => 'Descripción test',
                    'url' => 'http://localhost/',
                    'keywords' => 'cms,php',
                    'basepath' => '/',
                    'theme' => 'default',
                    'footer' => '© 2025 Mi CMS',
                    'multilang' => '0',
                    'editor' => 'tiny'
                ]
            ]
        ];

        file_put_contents($this->configFile, json_encode($jsonContent, JSON_PRETTY_PRINT));
    }

    protected function tearDown(): void
    {
        // --- Limpieza de archivos temporales ---
        if (file_exists($this->configFile)) {
            unlink($this->configFile);
        }
        if (is_dir($this->configDir)) {
            rmdir($this->configDir);
            rmdir(dirname($this->configDir)); // eliminar también /resources
        }

        // --- Limpieza de output buffers abiertos por ErrorConsole ---
        while (ob_get_level() > 1) {
            ob_end_clean();
        }

        parent::tearDown();
    }

    public function testBootInitializesContainerAndContext(): void
    {
        $boot = new Boot();

        $this->assertInstanceOf(Container::class, $boot->getContainer());
        $this->assertInstanceOf(Context::class, $boot->getContext());
    }

    public function testGetFromContainerResolvesClass(): void
    {
        $boot = new Boot();

        $instance = $boot->getFromContainer(DummyClass::class);

        $this->assertInstanceOf(DummyClass::class, $instance);
    }

    public function testLoadControllerCallsInitControllerAndInject(): void
    {
        $boot = new Boot();

        // Antes de cargar debe estar en false
        $this->assertFalse(DummyController::$initialized);

        $boot->loadController(DummyController::class);

        // Después de loadController debe estar en true
        $this->assertTrue(DummyController::$initialized);
    }

    public function testAutoloadClassLoadsFile(): void
    {
        $boot = new Boot();

        $className = 'TempDummyClass';
        $filePath  = LOCAL_DIR . '/TempDummyClass.php';

        file_put_contents($filePath, "<?php class $className {}");

        $this->assertFalse(class_exists($className, false));

        $boot->autoloadClass($className);

        $this->assertTrue(class_exists($className, false));

        unlink($filePath);
    }
}

class DummyClass {}

class DummyController
{
    public static bool $initialized = false;

    public function initControllerAndInject(): void
    {
        self::$initialized = true;
    }
}
