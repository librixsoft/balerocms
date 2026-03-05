<?php

declare(strict_types=1);

namespace Tests\App\Mapper;

use App\DTO\InstallerDTO;
use App\Mapper\InstallerMapper;
use Framework\Core\ConfigSettings;
use Framework\Utils\Hash;
use PHPUnit\Framework\TestCase;

final class InstallerMapperTest extends TestCase
{
    public function testMapAndSaveSettingsUsesDtoValuesAndHashesPassword(): void
    {
        $dto = new class extends InstallerDTO {
            public function getDbhost(): string { return 'localhost'; }
            public function getDbuser(): string { return 'root'; }
            public function getDbpass(): string { return 'secret'; }
            public function getDbname(): string { return 'balero'; }
            public function getTitle(): string { return 'Balero'; }
            public function getUrl(): string { return 'https://example.com'; }
            public function getDescription(): string { return 'desc'; }
            public function getKeywords(): string { return 'cms'; }
            public function getBasepath(): string { return ''; }
            public function getLastname(): string { return 'Lovelace'; }
            public function getFirstname(): string { return 'Ada'; }
            public function getUsername(): string { return 'admin'; }
            public function getEmail(): string { return 'admin@example.com'; }
            public function getPasswd(): string { return '1234'; }
        };

        $hash = $this->createMock(Hash::class);
        $hash->expects($this->once())
            ->method('genpwd')
            ->with('1234')
            ->willReturn('hashed-1234');

        $written = [];
        $config = $this->getMockBuilder(ConfigSettings::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__set', 'getFullBasepath'])
            ->getMock();

        $config->method('getFullBasepath')->willReturn('/auto/');
        $config->expects($this->exactly(14))
            ->method('__set')
            ->willReturnCallback(function (string $name, string $value) use (&$written): void {
                $written[$name] = $value;
            });

        (new InstallerMapper($hash))->mapAndSaveSettings($dto, $config);

        $this->assertSame('localhost', $written['dbhost']);
        $this->assertSame('/auto/', $written['basepath']);
        $this->assertSame('hashed-1234', $written['pass']);
        $this->assertSame('admin@example.com', $written['email']);
    }
}
