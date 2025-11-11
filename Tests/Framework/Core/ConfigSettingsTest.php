<?php

namespace Tests\Framework\Core;

use Framework\Core\ConfigSettings;
use Framework\Exceptions\ConfigException;
use PHPUnit\Framework\TestCase;

class ConfigSettingsTest extends TestCase
{
    private string $inlineJson;

    protected function setUp(): void
    {
        $this->inlineJson = json_encode([
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
        ], JSON_PRETTY_PRINT);
    }

    public function testLoadsInlineJsonContent(): void
    {
        $config = new ConfigSettings('/tmp/test.json', $this->inlineJson);
        $this->assertSame('localhost', $config->dbhost);
        $this->assertSame('admin', $config->username);
        $this->assertSame('Balero CMS', $config->title);
    }

    public function testSetUpdatesValue(): void
    {
        $config = new ConfigSettings('/tmp/test.json', $this->inlineJson);
        $config->email = 'new@example.com';
        $this->assertSame('new@example.com', $config->email);
    }

    public function testThrowsOnInvalidProperty(): void
    {
        $config = new ConfigSettings('/tmp/test.json', $this->inlineJson);
        $this->expectException(ConfigException::class);
        $config->nonexistent = 'value';
    }

    public function testGetFullBasepathGeneratesValidUrl(): void
    {
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $config = new ConfigSettings('/tmp/test.json', $this->inlineJson);
        $url = $config->getFullBasepath();
        $this->assertStringStartsWith('https://example.com', $url);
    }

    public function testThrowsIfFileNotFoundInProdMode(): void
    {
        $this->expectException(ConfigException::class);
        new ConfigSettings('/path/to/nonexistent.json');
    }
}
