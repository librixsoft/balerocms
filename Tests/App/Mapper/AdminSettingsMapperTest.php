<?php

declare(strict_types=1);

namespace Tests\App\Mapper;

use App\DTO\SettingsDTO;
use App\Mapper\AdminSettingsMapper;
use Framework\Core\ConfigSettings;
use PHPUnit\Framework\TestCase;

final class AdminSettingsMapperTest extends TestCase
{
    public function testMapAndSaveSettingsWritesAllExpectedFields(): void
    {
        $dto = new class extends SettingsDTO {
            public function getTitle(): string { return 'Title'; }
            public function getDescription(): string { return 'Desc'; }
            public function getKeywords(): string { return 'one,two'; }
            public function getDebug(): string { return 'true'; }
            public function getUrl(): string { return 'https://example.com'; }
            public function getTheme(): string { return 'dark'; }
            public function getLanguage(): string { return 'es'; }
            public function getFooter(): string { return 'Footer'; }
        };

        $written = [];
        $config = $this->getMockBuilder(ConfigSettings::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__set'])
            ->getMock();

        $config->expects($this->exactly(8))
            ->method('__set')
            ->willReturnCallback(function (string $name, string $value) use (&$written): void {
                $written[$name] = $value;
            });

        (new AdminSettingsMapper())->mapAndSaveSettings($dto, $config);

        $this->assertSame([
            'title' => 'Title',
            'description' => 'Desc',
            'keywords' => 'one,two',
            'debug' => 'true',
            'url' => 'https://example.com',
            'theme' => 'dark',
            'language' => 'es',
            'footer' => 'Footer',
        ], $written);
    }
}
