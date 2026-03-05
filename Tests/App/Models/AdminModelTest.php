<?php

declare(strict_types=1);

namespace Tests\App\Models;

use App\Models\AdminModel;
use Framework\Core\ConfigSettings;
use Framework\Core\Model;
use Framework\Database\MySQL;
use Framework\Exceptions\ModelException;
use Framework\Utils\Utils;
use PHPUnit\Framework\TestCase;

final class AdminModelTest extends TestCase
{
    private function makeModel(MySQL $db): AdminModel
    {
        $core = $this->createMock(Model::class);
        $core->method('getDb')->willReturn($db);
        return new AdminModel($this->createMock(ConfigSettings::class), $core, new Utils());
    }

    public function testPageCrudHelpers(): void
    {
        $db = $this->createMock(MySQL::class);
        $db->method('getRows')->willReturn([['id' => 1, 'sort_order' => 2]]);
        $db->method('getInsertId')->willReturn(9);
        $db->expects($this->atLeast(1))->method('query');
        $db->expects($this->atLeast(1))->method('get');

        $m = $this->makeModel($db);

        $this->assertSame(1, $m->getPagesCount());
        $this->assertSame(['id' => 1, 'sort_order' => 2], $m->getPageById(1));

        $id = $m->createPage([
            'virtual_title' => 'A', 'static_url' => 'Hola Mundo', 'virtual_content' => 'x',
            'visible' => 1, 'date' => '2026-03-05', 'sort_order' => 3,
        ]);
        $this->assertSame(9, $id);
        $this->assertTrue($m->updatePage(1, [
            'virtual_title' => 'B', 'static_url' => 'Otro Slug', 'virtual_content' => 'y', 'visible' => 1, 'sort_order' => 1,
        ]));
        $this->assertTrue($m->deletePage(1));
    }

    public function testBlocksCrudHelpersAndExceptionPath(): void
    {
        $db = $this->createMock(MySQL::class);
        $db->method('getRows')->willReturn([['id' => 1, 'name' => 'n', 'sort_order' => 1, 'content' => 'c']]);
        $db->method('getRow')->willReturn(['id' => 2, 'name' => 'b', 'sort_order' => 2, 'content' => 'z']);
        $db->method('getInsertId')->willReturn(5);

        $m = $this->makeModel($db);
        $this->assertSame(1, $m->getBlocksCount());
        $this->assertSame('n', $m->getBlocks()[0]['name']);
        $this->assertSame(2, $m->getBlockById(2)['id']);
        $this->assertSame(5, $m->createBlock(['name' => 'x', 'content' => 'y']));
        $this->assertTrue($m->updateBlock(2, ['name' => 'x', 'content' => 'y']));
        $this->assertTrue($m->deleteBlock(2));

        $db2 = $this->createMock(MySQL::class);
        $db2->method('query')->willThrowException(new \RuntimeException('sql'));
        $this->expectException(ModelException::class);
        $this->makeModel($db2)->getVirtualPages();
    }
}
