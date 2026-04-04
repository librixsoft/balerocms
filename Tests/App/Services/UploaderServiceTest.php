<?php

declare(strict_types=1);

namespace Tests\App\Services;

use App\Services\UploaderService;
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
        $uploader->method('image')->willReturn(['status' => 'ok', 'url' => '/assets/images/uploads/x.jpg', 'name' => 'x.jpg']);

        $service = new UploaderService();
        $this->injectProperty($service, 'uploader', $uploader);

        $ok = $service->uploadImage(['name' => 'x.jpg', 'error' => 0]);
        $this->assertSame('ok', $ok['status']);

        $this->expectException(\Framework\Exceptions\UploaderException::class);
        $service->uploadImage([]);
    }

    public function testLinkImagesGetAllMediaAndDelete(): void
    {
        $uploader = $this->createMock(Uploader::class);
        $uploader->expects($this->once())->method('removeRecordFromAllMetadata')->with(10, 'page');
        $uploader->expects($this->once())->method('addRecordToMetadata');
        $uploader->method('getAllMediaMetadata')->willReturn([['name' => 'a.jpg']]);
        $uploader->expects($this->once())->method('deleteMedia')->with('a.jpg');

        $service = new UploaderService();
        $this->injectProperty($service, 'uploader', $uploader);

        $service->unlinkImagesFromRecordJson(10, 'page');
        $service->linkImageToRecordJson('a.jpg', ['id' => 10, 'type' => 'page']);

        $this->assertSame([['name' => 'a.jpg']], $service->getAllMediaJson());
        $service->deleteMediaFile('a.jpg');
    }
}
