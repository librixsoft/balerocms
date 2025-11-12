<?php

namespace Tests\Framework\Core;

use Framework\Core\ConfigSettings;
use Framework\Exceptions\ConfigException;
use PHPUnit\Framework\TestCase;

class ConfigSettingsTest extends TestCase
{
    private string $tmpFile;

    protected function setUp(): void
    {
        // Creamos un archivo temporal
        $this->tmpFile = tempnam(sys_get_temp_dir(), 'config_');

        $data = [
            'config' => [
                'database' => [
                    'dbhost' => 'localhost',
                    'dbuser' => 'root',
                    'dbpass' => '1234',
                    'dbname' => 'balero'
                ],
                'admin' => [
                    'username' => 'admin',
                    'passwd' => 'secret',
                    'email' => 'admin@example.com',
                    'firstname' => 'John',
                    'lastname' => 'Doe'
                ],
                'site' => [
                    'language' => 'es',
                    'title' => 'Balero CMS',
                    'url' => 'https://balero.dev'
                ],
                'system' => [
                    'installed' => 'yes'
                ]
            ]
        ];

        file_put_contents($this->tmpFile, json_encode($data, JSON_PRETTY_PRINT));
    }

    protected function tearDown(): void
    {
        // Limpiamos el archivo temporal
        if (file_exists($this->tmpFile)) {
            unlink($this->tmpFile);
        }
    }

    public function testLoadsJsonFileContent(): void
    {
        $config = new ConfigSettings($this->tmpFile);
        $this->assertSame('localhost', $config->dbhost);
        $this->assertSame('admin', $config->username);
        $this->assertSame('Balero CMS', $config->title);
    }

    public function testSetUpdatesValue(): void
    {
        $config = new ConfigSettings($this->tmpFile);
        $config->email = 'new@example.com';
        $this->assertSame('new@example.com', $config->email);
    }

    public function testThrowsOnInvalidProperty(): void
    {
        $config = new ConfigSettings($this->tmpFile);
        $this->expectException(ConfigException::class);
        $config->nonexistent = 'value';
    }

    public function testGetFullBasepathGeneratesValidUrl(): void
    {
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $config = new ConfigSettings($this->tmpFile);
        $url = $config->getFullBasepath();
        $this->assertStringStartsWith('https://example.com', $url);
    }

    public function testThrowsIfFileNotFoundInProdMode(): void
    {
        $this->expectException(ConfigException::class);
        new ConfigSettings('/path/to/nonexistent.json');
    }
}
