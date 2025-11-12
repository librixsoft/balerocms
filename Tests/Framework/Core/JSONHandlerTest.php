<?php

namespace Tests\Framework\Core;

use Framework\Core\JSONHandler;
use Framework\Exceptions\JSONHandlerException;
use PHPUnit\Framework\TestCase;

class JSONHandlerTest extends TestCase
{
    private string $tempFile;

    protected function setUp(): void
    {
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
    }

    public function testCanReadJsonFile(): void
    {
        $handler = new JSONHandler($this->tempFile);
        $this->assertSame('Balero CMS', $handler->get('site/name'));
        $this->assertSame('test@example.com', $handler->get('site/admin/email'));
    }

    public function testReturnsEmptyStringForInvalidPath(): void
    {
        $handler = new JSONHandler($this->tempFile);
        $this->assertSame('', $handler->get('site/invalid'));
    }

    public function testSetUpdatesValueAndSavesFile(): void
    {
        $handler = new JSONHandler($this->tempFile);
        $handler->set('site/admin/email', 'new@example.com');

        $reloaded = new JSONHandler($this->tempFile);
        $this->assertSame('new@example.com', $reloaded->get('site/admin/email'));
    }

    public function testThrowsExceptionIfFileNotFound(): void
    {
        $this->expectException(JSONHandlerException::class);
        new JSONHandler('/path/to/nonexistent.json');
    }

    public function testThrowsExceptionForInvalidJson(): void
    {
        file_put_contents($this->tempFile, '{ invalid json }');
        $this->expectException(JSONHandlerException::class);
        new JSONHandler($this->tempFile);
    }
}
