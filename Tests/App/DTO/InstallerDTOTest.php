<?php

declare(strict_types=1);

namespace Tests\App\DTO;

use App\DTO\InstallerDTO;
use Framework\Http\RequestHelper;
use PHPUnit\Framework\TestCase;

final class InstallerDTOTest extends TestCase
{
    public function testFromRequestMapsInstallerPayload(): void
    {
        $request = $this->createMock(RequestHelper::class);
        $request->method('post')->willReturnMap([
            ['dbhost', null, 'localhost'],
            ['dbuser', null, 'root'],
            ['dbpass', null, 'secret'],
            ['dbname', null, 'balero'],
            ['basepath', null, '/cms/'],
            ['title', null, 'Balero'],
            ['url', null, 'https://example.com'],
            ['keywords', null, 'cms'],
            ['description', null, 'desc'],
            ['username', null, 'admin'],
            ['passwd', null, '1234'],
            ['passwd2', null, '1234'],
            ['email', null, 'admin@example.com'],
            ['firstname', null, 'Ada'],
            ['lastname', null, 'Lovelace'],
        ]);

        $dto = new InstallerDTO();
        $dto->fromRequest($request);

        $ref = new \ReflectionClass($dto);

        $this->assertSame('localhost', $ref->getProperty('dbhost')->getValue($dto));
        $this->assertSame('root', $ref->getProperty('dbuser')->getValue($dto));
        $this->assertSame('secret', $ref->getProperty('dbpass')->getValue($dto));
        $this->assertSame('balero', $ref->getProperty('dbname')->getValue($dto));
        $this->assertSame('/cms/', $ref->getProperty('basepath')->getValue($dto));
        $this->assertSame('Balero', $ref->getProperty('title')->getValue($dto));
        $this->assertSame('https://example.com', $ref->getProperty('url')->getValue($dto));
        $this->assertSame('cms', $ref->getProperty('keywords')->getValue($dto));
        $this->assertSame('desc', $ref->getProperty('description')->getValue($dto));
        $this->assertSame('admin', $ref->getProperty('username')->getValue($dto));
        $this->assertSame('1234', $ref->getProperty('passwd')->getValue($dto));
        $this->assertSame('1234', $ref->getProperty('passwd2')->getValue($dto));
        $this->assertSame('admin@example.com', $ref->getProperty('email')->getValue($dto));
        $this->assertSame('Ada', $ref->getProperty('firstname')->getValue($dto));
        $this->assertSame('Lovelace', $ref->getProperty('lastname')->getValue($dto));
    }
}
