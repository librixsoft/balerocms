<?php

declare(strict_types=1);

namespace Tests\App\Models;

use App\Models\PageModel;
use Framework\Core\Model;
use Framework\Database\MySQL;
use Framework\Exceptions\ModelException;
use PHPUnit\Framework\TestCase;

final class PageModelTest extends TestCase
{
    public function testGetVirtualPagesAndBySlug(): void
    {
        $db = $this->createMock(MySQL::class);
        $db->expects($this->exactly(2))->method('query');
        $db->expects($this->exactly(2))->method('get');
        $db->method('getRows')->willReturn([['id' => 1]]);
        $db->method('getRow')->willReturn(['id' => 1, 'static_url' => 'home']);

        $modelCore = $this->createMock(Model::class);
        $modelCore->method('getDb')->willReturn($db);

        $m = new PageModel($modelCore);
        $this->assertSame([['id' => 1]], $m->getVirtualPages());
        $this->assertSame(['id' => 1, 'static_url' => 'home'], $m->getVirtualPageBySlug('home'));
    }

    public function testGetVirtualPagesThrowsModelExceptionOnError(): void
    {
        $db = $this->createMock(MySQL::class);
        $db->method('query')->willThrowException(new \RuntimeException('db down'));
        $modelCore = $this->createMock(Model::class);
        $modelCore->method('getDb')->willReturn($db);

        $this->expectException(ModelException::class);
        (new PageModel($modelCore))->getVirtualPages();
    }
}
