<?php

declare(strict_types=1);

namespace Tests\Framework\Database;

use Framework\Database\MySQL;
use Framework\Exceptions\MySQLException;
use PHPUnit\Framework\TestCase;

class FakeResult
{
    public int $num_rows;
    private array $rows;
    private int $position = 0;
    public int $freed = 0;

    public function __construct(array $rows = [])
    {
        $this->rows = $rows;
        $this->num_rows = count($rows);
    }

    public function fetch_array(int $mode = MYSQLI_BOTH): ?array
    {
        if ($this->position >= count($this->rows)) {
            return null;
        }

        return $this->rows[$this->position++];
    }

    public function free(): void
    {
        $this->freed++;
    }
}

class FakeStmt
{
    public string $error = '';
    public bool $executeResult = true;
    public ?FakeResult $result = null;
    public array $bound = [];

    public function bind_param(string $types, mixed &...$vars): bool
    {
        $this->bound = $vars;
        return true;
    }

    public function execute(?array $params = null): bool
    {
        return $this->executeResult;
    }

    public function get_result(): ?FakeResult
    {
        return $this->result;
    }

    public function close(): void {}
}

class FakeMysqli
{
    public string $connect_error = '';
    public string $error = '';
    public int $insert_id = 0;
    public bool $setCharsetResult = true;
    public bool $queryShouldFail = false;
    public ?FakeResult $queryResult = null;
    public bool $prepareShouldFail = false;
    public ?FakeStmt $stmt = null;
    public bool $multiQueryResult = true;
    public ?FakeResult $storeResult = null;
    public array $moreResultsQueue = [];
    public string $realEscapeReturn = '';

    public function set_charset(string $charset): bool
    {
        return $this->setCharsetResult;
    }

    public function query(string $query): FakeResult|bool
    {
        if ($this->queryShouldFail) {
            return false;
        }

        return $this->queryResult ?? new FakeResult([]);
    }

    public function prepare(string $query): FakeStmt|false
    {
        if ($this->prepareShouldFail) {
            return false;
        }

        return $this->stmt ?? new FakeStmt();
    }

    public function multi_query(string $query): bool
    {
        return $this->multiQueryResult;
    }

    public function store_result(): FakeResult|false
    {
        return $this->storeResult ?? false;
    }

    public function more_results(): bool
    {
        if ($this->moreResultsQueue === []) {
            return false;
        }

        return (bool) array_shift($this->moreResultsQueue);
    }

    public function next_result(): bool
    {
        return true;
    }

    public function real_escape_string(string $value): string
    {
        return $this->realEscapeReturn !== '' ? $this->realEscapeReturn : addslashes($value);
    }
}

class TestableMySQL extends MySQL
{
    public function __construct(private FakeMysqli $fake) {}

    protected function createConnection(string $host, string $user, string $pass, ?string $dbname = null): object
    {
        return $this->fake;
    }
}

final class MySQLTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!defined('MYSQLI_ASSOC')) {
            define('MYSQLI_ASSOC', 1);
        }
        if (!defined('MYSQLI_BOTH')) {
            define('MYSQLI_BOTH', 3);
        }
    }
    public function testConnectSuccessSetsStatusAndCharset(): void
    {
        $fake = new FakeMysqli();
        $db = new TestableMySQL($fake);

        $db->connect('localhost', 'user', 'pass', 'db');

        $this->assertSame($fake, $db->getConn());
        $this->assertTrue($db->isStatus());
    }

    public function testConnectThrowsOnConnectionError(): void
    {
        $fake = new FakeMysqli();
        $fake->connect_error = 'nope';
        $db = new TestableMySQL($fake);

        $this->expectException(MySQLException::class);
        $this->expectExceptionMessage('Failed to connect to MySQL');

        $db->connect('localhost', 'user', 'pass');
    }

    public function testConnectThrowsWhenCharsetFails(): void
    {
        $fake = new FakeMysqli();
        $fake->setCharsetResult = false;
        $fake->error = 'charset error';
        $db = new TestableMySQL($fake);

        $this->expectException(MySQLException::class);
        $this->expectExceptionMessage('Error loading character set');

        $db->connect('localhost', 'user', 'pass');
    }

    public function testQueryWithoutParamsStoresResult(): void
    {
        $fake = new FakeMysqli();
        $fake->queryResult = new FakeResult([['id' => 1]]);
        $db = new TestableMySQL($fake);
        $db->setConn($fake);

        $db->query('SELECT 1');

        $this->assertInstanceOf(FakeResult::class, $db->getResult());
        $this->assertSame(1, $db->getResult()->num_rows);
    }

    public function testQueryWithoutParamsThrowsOnSqlError(): void
    {
        $fake = new FakeMysqli();
        $fake->queryShouldFail = true;
        $db = new TestableMySQL($fake);
        $db->setConn($fake);

        $this->expectException(MySQLException::class);
        $this->expectExceptionMessage('SQL syntax error');

        $db->query('BAD QUERY');
    }

    public function testQueryWithParamsStoresResult(): void
    {
        $fake = new FakeMysqli();
        $stmt = new FakeStmt();
        $stmt->result = new FakeResult([['id' => 2]]);
        $fake->stmt = $stmt;

        $db = new TestableMySQL($fake);
        $db->setConn($fake);

        $db->query('SELECT * FROM users WHERE id = ?', [2]);

        $this->assertInstanceOf(FakeResult::class, $db->getResult());
        $this->assertSame(2, $db->getResult()->fetch_array(MYSQLI_ASSOC)['id']);
    }

    public function testQueryWithParamsThrowsOnPrepareFailure(): void
    {
        $fake = new FakeMysqli();
        $fake->prepareShouldFail = true;
        $fake->error = 'prepare failed';
        $db = new TestableMySQL($fake);
        $db->setConn($fake);

        $this->expectException(MySQLException::class);
        $this->expectExceptionMessage('Failed to prepare statement');

        $db->query('SELECT 1 WHERE id = ?', [1]);
    }

    public function testQueryWithParamsThrowsOnExecuteFailure(): void
    {
        $fake = new FakeMysqli();
        $stmt = new FakeStmt();
        $stmt->executeResult = false;
        $stmt->error = 'execute failed';
        $fake->stmt = $stmt;

        $db = new TestableMySQL($fake);
        $db->setConn($fake);

        $this->expectException(MySQLException::class);
        $this->expectExceptionMessage('Failed to execute statement');

        $db->query('SELECT 1 WHERE id = ?', [1]);
    }

    public function testGetThrowsWhenNoValidResult(): void
    {
        $db = new MySQL();
        $db->setResult(false);

        $this->expectException(MySQLException::class);
        $this->expectExceptionMessage('No valid result set');

        $db->get();
    }

    public function testGetPopulatesRowsAndRow(): void
    {
        $db = new MySQL();
        $result = new FakeResult([
            ['id' => 1],
            ['id' => 2],
        ]);
        $db->setResult($result);

        $db->get();

        $this->assertSame([['id' => 1], ['id' => 2]], $db->getRows());
        $this->assertSame(['id' => 1], $db->getRow());
        $this->assertSame(2, $db->num_rows());
    }

    public function testCreateExecutesMultiQueryAndFreesResults(): void
    {
        $fake = new FakeMysqli();
        $fake->storeResult = new FakeResult([['ok' => 1]]);
        $fake->moreResultsQueue = [true, false];

        $db = new TestableMySQL($fake);
        $db->setConn($fake);

        $db->create('CREATE TABLE demo (id INT);');

        $this->assertSame(2, $fake->storeResult->freed);
    }

    public function testCreateThrowsOnFailure(): void
    {
        $fake = new FakeMysqli();
        $fake->multiQueryResult = false;
        $db = new TestableMySQL($fake);
        $db->setConn($fake);

        $this->expectException(MySQLException::class);
        $this->expectExceptionMessage('Failed to create table');

        $db->create('CREATE TABLE demo (id INT);');
    }

    public function testEscapeAndInsertIdAndQueryArray(): void
    {
        $fake = new FakeMysqli();
        $fake->realEscapeReturn = 'escaped';
        $fake->insert_id = 42;

        $db = new TestableMySQL($fake);
        $db->setConn($fake);
        $db->setRows([['id' => 1]]);

        $this->assertSame('escaped', $db->escape("O'Reilly"));
        $this->assertSame(42, $db->getInsertId());
        $this->assertSame([['id' => 1]], $db->queryArray());
    }

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
