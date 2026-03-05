<?php

declare(strict_types=1);

namespace Tests\App\DTO;

use App\DTO\SettingsDTO;
use Framework\Http\RequestHelper;
use PHPUnit\Framework\TestCase;

final class SettingsDTOTest extends TestCase
{
    public function testFromRequestMapsAllExpectedFields(): void
    {
        $request = $this->createMock(RequestHelper::class);
        $request->method('post')->willReturnMap([
            ['title', null, 'Mi sitio'],
            ['description', null, 'Descripcion'],
            ['debug', null, 'true'],
            ['keywords', null, 'cms,php'],
            ['url', null, 'https://example.com'],
            ['theme', null, 'default'],
            ['language', null, 'es'],
            ['footer', null, 'footer text'],
        ]);

        $dto = new SettingsDTO();
        $dto->fromRequest($request);

        $ref = new \ReflectionClass($dto);

        $this->assertSame('Mi sitio', $ref->getProperty('title')->getValue($dto));
        $this->assertSame('Descripcion', $ref->getProperty('description')->getValue($dto));
        $this->assertSame('true', $ref->getProperty('debug')->getValue($dto));
        $this->assertSame('cms,php', $ref->getProperty('keywords')->getValue($dto));
        $this->assertSame('https://example.com', $ref->getProperty('url')->getValue($dto));
        $this->assertSame('default', $ref->getProperty('theme')->getValue($dto));
        $this->assertSame('es', $ref->getProperty('language')->getValue($dto));
        $this->assertSame('footer text', $ref->getProperty('footer')->getValue($dto));
    }
}
