<?php

declare(strict_types=1);

namespace Tests\App\Services;

use App\Services\UploaderService;
use App\Models\AdminModel;
use Framework\Core\ConfigSettings;
use Framework\IO\Uploader;
use PHPUnit\Framework\TestCase;

final class UploaderServiceTest extends TestCase
{
    private function injectProperty($object, $propertyName, $value): void
    {
        $r = new \ReflectionClass($object);
        $p = $r->getProperty($propertyName);
        $p->setAccessible(true);
        $p->setValue($object, $value);
    }

    public function testUploadImageOkErrorAndEmptyFile(): void
    {
        $uploader = $this->createMock(Uploader::class);
        $uploader->method('image')->willReturn(['url' => '/assets/images/uploads/x.jpg', 'hash' => 'hash123']);

        $adminModel = $this->createMock(AdminModel::class);
        $configSettings = new \stdClass();
        $configSettings->installed = 'no';

        $service = new UploaderService();
        $this->injectProperty($service, 'uploader', $uploader);
        $this->injectProperty($service, 'adminModel', $adminModel);
        $this->injectProperty($service, 'configSettings', $configSettings);

        $ok = $service->uploadImage(['name' => 'x']);
        $this->assertSame('ok', $ok['status']);

        $this->assertSame('error', $service->uploadImage([])['status']);

        $uploader2 = $this->createMock(Uploader::class);
        $uploader2->method('image')->willThrowException(new \RuntimeException('boom'));
        $this->injectProperty($service, 'uploader', $uploader2);
        
        $err = $service->uploadImage(['name' => 'x']);
        $this->assertSame('error', $err['status']);
    }

    public function testLinkImagesGetAllMediaAndDelete(): void
    {
        $uploader = $this->createMock(Uploader::class);
        $uploader->expects($this->once())->method('removeRecordFromAllMetadata')->with(10, 'page');
        $uploader->expects($this->exactly(2))->method('addRecordToMetadata');
        $uploader->method('getAllMediaMetadata')->willReturn([['hash' => 'a']]);
        $uploader->expects($this->once())->method('deleteMedia')->with('a');

        $adminModel = $this->createMock(AdminModel::class);
        $configSettings = new \stdClass();
        $configSettings->installed = 'no';

        $service = new UploaderService();
        $this->injectProperty($service, 'uploader', $uploader);
        $this->injectProperty($service, 'adminModel', $adminModel);
        $this->injectProperty($service, 'configSettings', $configSettings);

        $html = '<img src="/assets/images/uploads/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa.jpg">' .
                '<img src="/assets/images/uploads/bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb.png">';
        $service->linkImagesToRecord($html, 10, 'page', '/hola');

        $this->assertSame([['hash' => 'a']], $service->getAllMedia());
        $service->deleteMedia('a');
    }
}
