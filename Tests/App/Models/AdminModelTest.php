<?php

declare(strict_types=1);

namespace Tests\App\Models;

use App\Models\AdminModel;
use Framework\Core\ConfigSettings;
use Framework\Core\Model;
use Framework\Database\MySQL;
use Framework\Exceptions\ModelException;
use Framework\Utils\Utils;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function testBlocksCrudHelpers(): void
    {
        $db = $this->createMock(MySQL::class);
        $db->method('getRows')->willReturn([['id' => 1, 'name' => 'n', 'sort_order' => 1, 'content' => 'c']]);
        $db->method('getRow')->willReturn(['id' => 2, 'name' => 'b', 'sort_order' => 2, 'content' => 'z']);
        $db->method('getInsertId')->willReturn(5);

        $m = $this->makeModel($db);
        $this->assertSame(1, $m->getBlocksCount());
        $this->assertSame('n', $m->getBlocks()[0]['name']);
        $this->assertSame(2, $m->getBlockById(2)['id']);
        
        // Test createBlock with and without sort_order
        $this->assertSame(5, $m->createBlock(['name' => 'x', 'content' => 'y', 'sort_order' => 10]));
        $this->assertSame(5, $m->createBlock(['name' => 'x', 'content' => 'y']));
        
        // Test updateBlock with and without sort_order
        $this->assertTrue($m->updateBlock(2, ['name' => 'x', 'content' => 'y', 'sort_order' => 20]));
        $this->assertTrue($m->updateBlock(2, ['name' => 'x', 'content' => 'y']));
        
        $this->assertTrue($m->deleteBlock(2));
    }

    public function testMediaMethods(): void
    {
        $db = $this->createMock(MySQL::class);
        $m = $this->makeModel($db);

        $mediaData = [
            'name' => 'test.jpg',
            'original_name' => 'orig.jpg',
            'extension' => 'jpg',
            'mime' => 'image/jpeg',
            'size_bytes' => 2048576, // ~1.95 MB
            'width' => 100,
            'height' => 100,
            'url' => '/test.jpg',
            'uploaded_at' => '2026-01-01',
            'records' => [['id' => 1, 'type' => 'page']]
        ];

        // We use any() for query since many methods call it
        $db->method('query');

        // insertMedia
        $m->insertMedia($mediaData);

        // getMediaByName
        $db->method('getRow')->willReturn([
            'name' => 'test.jpg',
            'records' => json_encode($mediaData['records']),
            'size_bytes' => 2048576
        ]);
        $result = $m->getMediaByName('test.jpg');
        $this->assertSame('test.jpg', $result['name']);
        $this->assertSame('1.95 MB', $result['size_formatted']);
        $this->assertStringContainsString('page #1', $result['records_summary']);

        // getAllMedia (KB test)
        $db->method('getRows')->willReturn([[
            'name' => 'small.png',
            'size_bytes' => 1024,
            'records' => '[]'
        ]]);
        $all = $m->getAllMedia();
        $this->assertCount(1, $all);
        $this->assertSame('1 KB', $all[0]['size_formatted']);
        $this->assertSame('Not linked', $all[0]['records_summary']);

        // updateMediaRecords
        $m->updateMediaRecords('test.jpg', []);

        // deleteMedia
        $m->deleteMedia('test.jpg');
        
        // removeRecordFromAllMediaRecords
        $db->method('getRows')->willReturnOnConsecutiveCalls(
            [['name' => 'test.jpg', 'records' => json_encode([['id' => 1, 'type' => 'page'], ['id' => 2, 'type' => 'block']])]]
        );
        $m->removeRecordFromAllMediaRecords(1, 'page');
    }

    public function testNormalizeMediaRowHandlesInvalidJson(): void
    {
        $db = $this->createMock(MySQL::class);
        $db->method('getRow')->willReturn([
            'name' => 'bad.jpg',
            'records' => 'not-json'
        ]);
        $m = $this->makeModel($db);
        $res = $m->getMediaByName('bad.jpg');
        $this->assertSame([], $res['records']);
    }

    #[DataProvider('exceptionProvider')]
    public function testExceptions(string $method, array $args): void
    {
        $db = $this->createMock(MySQL::class);
        $db->method('query')->willThrowException(new \RuntimeException('fail'));
        
        $m = $this->makeModel($db);
        
        $this->expectException(ModelException::class);
        call_user_func_array([$m, $method], $args);
    }

    public function testRemoveRecordFromAllMediaRecordsHandlesInvalidJson(): void
    {
        $db = $this->createMock(MySQL::class);
        $db->method('getRows')->willReturn([
            ['name' => 'bad.jpg', 'records' => 'invalid-json']
        ]);
        $m = $this->makeModel($db);
        
        // Should not throw exception, just continue and call updateMediaRecords with empty array
        // Expected calls:
        // 1. SELECT name, records FROM media
        // 2. UPDATE media SET records = ? WHERE name = ?
        $db->expects($this->exactly(2))->method('query');
        $m->removeRecordFromAllMediaRecords(1, 'page');
    }

    public static function exceptionProvider(): array
    {
        return [
            ['getVirtualPages', []],
            ['getBlocks', []],
            ['deletePage', [1]],
            ['getBlockById', [1]],
            ['createBlock', [['name' => 'x', 'content' => 'y']]],
            ['updateBlock', [1, ['name' => 'x', 'content' => 'y']]],
            ['deleteBlock', [1]],
            ['getMediaByName', ['x']],
            ['insertMedia', [[
                'name' => 'x',
                'extension' => 'jpg',
                'mime' => 'image/jpeg',
                'url' => '/',
                'uploaded_at' => '2026-01-01'
            ]]],
            ['updateMediaRecords', ['x', []]],
            ['getAllMedia', []],
            ['deleteMedia', ['x']],
            ['removeRecordFromAllMediaRecords', [1, 'page']],
        ];
    }
}
