<?php

use Framework\Core\JSONHandler;
use PHPUnit\Framework\TestCase;

final class JsonHandlerTest extends TestCase
{
    private string $file;

    public function setUp(): void
    {
        // Ruta temporal para el JSON de prueba
        $this->file = __DIR__ . '/../../resources/config/test_config.json';

        // Creamos un JSON de prueba dinámicamente
        $jsonContent = [
            "config" => [
                "database" => [
                    "dbhost" => "localhost",
                    "dbuser" => "root",
                    "dbpass" => "1234",
                    "dbname" => "cms"
                ],
                "site" => [
                    "title" => "Mi CMS",
                    "theme" => "default"
                ]
            ]
        ];

        file_put_contents($this->file, json_encode($jsonContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function tearDown(): void
    {
        if (file_exists($this->file)) {
            unlink($this->file);
        }
    }

    public function testGetValue(): void
    {
        $jsonHandler = new JSONHandler($this->file);

        $dbHost = $jsonHandler->get('config/database/dbhost');
        $this->assertEquals('localhost', $dbHost);

        $theme = $jsonHandler->get('config/site/theme');
        $this->assertEquals('default', $theme);

        // Valor no existente devuelve ''
        $nonExistent = $jsonHandler->get('config/nonexistent');
        $this->assertEquals('', $nonExistent);
    }

    public function testSetValue(): void
    {
        $jsonHandler = new JSONHandler($this->file);

        // Cambiar valores
        $jsonHandler->set('config/database/dbhost', '127.0.0.1');
        $jsonHandler->set('config/site/theme', 'darkmode');

        // Verificar cambios
        $this->assertEquals('127.0.0.1', $jsonHandler->get('config/database/dbhost'));
        $this->assertEquals('darkmode', $jsonHandler->get('config/site/theme'));
    }

    public function testSetValueBlank(): void
    {
        $jsonHandler = new JSONHandler($this->file);

        // Asignar valor vacío
        $jsonHandler->set('config/site/theme', '');

        // Leer devuelve ''
        $this->assertEquals('', $jsonHandler->get('config/site/theme'));
    }

    public function testSetNewPathCreatesNodes(): void
    {
        $jsonHandler = new JSONHandler($this->file);

        // Crear ruta nueva que no existe
        $jsonHandler->set('config/system/installed', 'no');

        // Debe poder leerse
        $this->assertEquals('no', $jsonHandler->get('config/system/installed'));
    }
}
