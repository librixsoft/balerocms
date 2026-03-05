<?php

declare(strict_types=1);

namespace Tests\App\Services;

use App\Services\UploaderService;
use Framework\IO\Uploader;
use PHPUnit\Framework\TestCase;

final class UploaderServiceTest extends TestCase
{
    public function testUploadImageOkErrorAndEmptyFile(): void
    {
        $uploader = $this->createMock(Uploader::class);
        $uploader->method('image')->willReturn('/assets/images/uploads/x.jpg');

        $service = new UploaderService();
        $r = new \ReflectionClass($service);
        $p = $r->getProperty('uploader');
        $p->setAccessible(true);
        $p->setValue($service, $uploader);

        $ok = $service->uploadImage(['name' => 'x']);
        $this->assertSame('ok', $ok['status']);

        $this->assertSame('error', $service->uploadImage([])['status']);

        $uploader2 = $this->createMock(Uploader::class);
        $uploader2->method('image')->willThrowException(new \RuntimeException('boom'));
        $p->setValue($service, $uploader2);
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

        $service = new UploaderService();
        $r = new \ReflectionClass($service);
        $p = $r->getProperty('uploader');
        $p->setAccessible(true);
        $p->setValue($service, $uploader);

        $html = '<img src="/assets/images/uploads/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa.jpg">' .
                '<img src="/assets/images/uploads/bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb.png">';
        $service->linkImagesToRecord($html, 10, 'page', '/hola');

        $this->assertSame([['hash' => 'a']], $service->getAllMedia());
        $service->deleteMedia('a');
    }
}
