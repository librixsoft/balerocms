<?php

use Framework\Core\ConfigSettings;
use Framework\Core\JSONHandler;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    private string $jsonFile;

    public function setUp(): void
    {
        // Ruta temporal para el JSON de prueba
        $this->jsonFile = __DIR__ . '/../../resources/config/test_configsettings.json';

        // Creamos un JSON de prueba dinámicamente
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
                'system' => [
                    'installed' => '1'
                ],
                'site' => [
                    'title' => 'Mi CMS',
                    'description' => 'Descripción del sitio',
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

        // Crear directorio si no existe
        if (!is_dir(dirname($this->jsonFile))) {
            mkdir(dirname($this->jsonFile), 0777, true);
        }

        file_put_contents($this->jsonFile, json_encode($jsonContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function tearDown(): void
    {
        if (file_exists($this->jsonFile)) {
            unlink($this->jsonFile);
        }
    }

    public function testJSONHandlerGetAndSet(): void
    {
        $json = new JSONHandler($this->jsonFile);

        // Leer valor
        $dbHost = $json->get('/config/database/dbhost');
        $this->assertEquals('localhost', $dbHost);

        // Modificar valor
        $json->set('/config/database/dbhost', '127.0.0.1');
        $dbHostModified = $json->get('/config/database/dbhost');
        $this->assertEquals('127.0.0.1', $dbHostModified);
    }

    public function testConfigSettingsGetAndSet(): void
    {
        // Pasamos la ruta del JSON de prueba al constructor
        $config = new ConfigSettings($this->jsonFile);

        // Comprobamos lectura inicial
        $this->assertEquals('localhost', $config->dbhost);
        $this->assertEquals('default', $config->theme);
        $this->assertEquals('admin', $config->username);

        // Cambiamos valores usando magic setter
        $config->dbhost = '192.168.0.1';
        $config->theme = 'darkmode';
        $config->username = 'superadmin';

        // Validamos cambios usando magic getter
        $this->assertEquals('192.168.0.1', $config->dbhost);
        $this->assertEquals('darkmode', $config->theme);
        $this->assertEquals('superadmin', $config->username);

        // Validar que también se modificó en el JSON
        $json = new JSONHandler($this->jsonFile);
        $this->assertEquals('192.168.0.1', $json->get('/config/database/dbhost'));
        $this->assertEquals('darkmode', $json->get('/config/site/theme'));
        $this->assertEquals('superadmin', $json->get('/config/admin/username'));
    }

    public function testInvalidPropertyThrowsException(): void
    {
        $this->expectException(\Exception::class);

        $config = new ConfigSettings($this->jsonFile);
        $config->nonexistent = 'value'; // debería lanzar Exception
    }
}
