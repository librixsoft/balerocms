<?php

declare(strict_types=1);

namespace Tests\Framework\Database;

use Framework\Database\MySQL;
use PHPUnit\Framework\TestCase;

final class MySQLTest extends TestCase
{
    public function testSettersAndGettersForStateFields(): void
    {
        $db = new MySQL();

        $db->setError('boom');
        $db->setRows([['id' => 1]]);
        $db->setRow(['id' => 1]);
        $db->setStatus(true);
        $db->setResult(false);

        $this->assertTrue($db->isError());
        $this->assertSame('boom', $db->mySQLError());
        $this->assertSame([['id' => 1]], $db->getRows());
        $this->assertSame(['id' => 1], $db->getRow());
        $this->assertTrue($db->isStatus());
        $this->assertSame(0, $db->num_rows());
        $this->assertFalse($db->getResult());
    }
}
