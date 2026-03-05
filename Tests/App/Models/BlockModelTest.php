<?php

declare(strict_types=1);

namespace Tests\App\Models;

use App\Models\BlockModel;
use Framework\Core\Model;
use Framework\Database\MySQL;
use Framework\Exceptions\ModelException;
use PHPUnit\Framework\TestCase;

final class BlockModelTest extends TestCase
{
    public function testGetBlocksReturnsRows(): void
    {
        $db = $this->createMock(MySQL::class);
        $db->expects($this->once())->method('query');
        $db->expects($this->once())->method('get');
        $db->method('getRows')->willReturn([['id' => 2]]);

        $modelCore = $this->createMock(Model::class);
        $modelCore->method('getDb')->willReturn($db);

        $m = new BlockModel($modelCore);
        $this->assertSame([['id' => 2]], $m->getBlocks());
    }

    public function testGetBlocksThrowsModelException(): void
    {
        $db = $this->createMock(MySQL::class);
        $db->method('query')->willThrowException(new \RuntimeException('sql'));
        $modelCore = $this->createMock(Model::class);
        $modelCore->method('getDb')->willReturn($db);

        $this->expectException(ModelException::class);
        (new BlockModel($modelCore))->getBlocks();
    }
}
