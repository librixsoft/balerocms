<?php

namespace Tests\Framework\Core;

use Framework\Core\JSONHandler;
use Framework\Exceptions\JSONHandlerException;
use Framework\Testing\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(JSONHandler::class)]
#[TestDox('Test de la clase JSONHandler')]
class JSONHandlerTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempFile = tempnam(sys_get_temp_dir(), 'jsonhandler_');
        file_put_contents($this->tempFile, json_encode([
            'site' => [
                'name' => 'Balero CMS',
                'admin' => ['email' => 'test@example.com']
            ]
        ], JSON_PRETTY_PRINT));
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
        parent::tearDown();
    }

    #[Test]
    #[TestDox('Puede leer correctamente los valores desde el archivo JSON')]
    public function testCanReadJsonFile(): void
    {
        $handler = new JSONHandler($this->tempFile);
        $this->assertSame('Balero CMS', $handler->get('site/name'));
        $this->assertSame('test@example.com', $handler->get('site/admin/email'));
    }

    #[Test]
    #[TestDox('Devuelve cadena vacía si la ruta no existe')]
    public function testReturnsEmptyStringForInvalidPath(): void
    {
        $handler = new JSONHandler($this->tempFile);
        $this->assertSame('', $handler->get('site/invalid'));
    }

    #[Test]
    #[TestDox('Actualiza un valor y lo guarda correctamente en el archivo')]
    public function testSetUpdatesValueAndSavesFile(): void
    {
        $handler = new JSONHandler($this->tempFile);
        $handler->set('site/admin/email', 'new@example.com');

        $reloaded = new JSONHandler($this->tempFile);
        $this->assertSame('new@example.com', $reloaded->get('site/admin/email'));
    }

    #[Test]
    #[TestDox('Lanza excepción si el archivo JSON no existe')]
    public function testThrowsExceptionIfFileNotFound(): void
    {
        $this->expectException(JSONHandlerException::class);
        new JSONHandler('/path/to/nonexistent.json');
    }

    #[Test]
    #[TestDox('Lanza excepción si el JSON del archivo es inválido')]
    public function testThrowsExceptionForInvalidJson(): void
    {
        file_put_contents($this->tempFile, '{ invalid json }');
        $this->expectException(JSONHandlerException::class);
        new JSONHandler($this->tempFile);
    }
}
