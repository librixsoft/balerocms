<?php

/**
 * Balero CMS
 * @author Anibal Gomez <balerocms@gmail.com>
 * @license GNU General Public License
 */

namespace Framework\Database;

use Framework\Core\ConfigSettings;
use Framework\Exceptions\MySQLException;
use mysqli;
use Throwable;

class MySQL
{
    private ConfigSettings $config;
    private object $conn;

    /**
     * Puede ser un resultado de consulta, false o null (al inicio)
     */
    private object|bool|null $result = null;

    private bool|string $error = false;
    private array $rows = [];
    private ?array $row = null;

    private bool $status = false;

    protected function createConnection(string $host, string $user, string $pass, ?string $dbname = null): object
    {
        if ($dbname) {
            return @new mysqli($host, $user, $pass, $dbname);
        }

        return @new mysqli($host, $user, $pass);
    }

    private function getConnectionError(): string
    {
        return property_exists($this->conn, 'connect_error')
            ? (string) $this->conn->connect_error
            : '';
    }

    private function getConnectionErrorMessage(): string
    {
        return property_exists($this->conn, 'error')
            ? (string) $this->conn->error
            : '';
    }

    public function connect(string $host, string $user, string $pass, ?string $dbname = null): void
    {
        $this->conn = $this->createConnection($host, $user, $pass, $dbname);

        $this->status = $this->getConnectionError() === '';
        if (!$this->status) {
            throw new MySQLException("Failed to connect to MySQL: " . $this->getConnectionError());
        }

        if (!$this->conn->set_charset("utf8mb4")) {
            throw new MySQLException("Error loading character set utf8mb4: " . $this->getConnectionErrorMessage());
        }
    }

    public function query(string $query, array $params = []): void
    {
        try {
            if (empty($params)) {
                $res = $this->conn->query($query);
                if ($res === false) {
                    throw new MySQLException("SQL syntax error in query: " . $query);
                }
                $this->result = $res;
            } else {
                $stmt = $this->conn->prepare($query);
                if (!$stmt) {
                    throw new MySQLException("Failed to prepare statement: " . $this->conn->error);
                }

                $types = str_repeat('s', count($params));
                $bindParams = [];
                foreach ($params as $key => $value) {
                    $bindParams[$key] = &$params[$key];
                }
                $stmt->bind_param($types, ...$bindParams);

                if (!$stmt->execute()) {
                    throw new MySQLException("Failed to execute statement: " . $stmt->error);
                }

                $this->result = $stmt->get_result();
                $stmt->close();
            }
        } catch (Throwable $e) {
            throw new MySQLException("Error executing query: " . $e->getMessage(), 0, $e);
        }
    }

    public function get(): void
    {
        try {
            if (!is_object($this->result) || !method_exists($this->result, 'fetch_array')) {
                throw new MySQLException("No valid result set available for fetching.");
            }

            $this->rows = [];
            $fetchMode = defined('MYSQLI_ASSOC') ? \MYSQLI_ASSOC : 1;
            while ($row = $this->result->fetch_array($fetchMode)) {
                $this->rows[] = $row;
            }

            $this->row = $this->rows[0] ?? null;

        } catch (Throwable $e) {
            throw new MySQLException("Error while fetching query result: " . $e->getMessage(), 0, $e);
        }
    }

    public function num_rows(): int
    {
        if (is_object($this->result)) {
            if (property_exists($this->result, 'num_rows')) {
                return (int) $this->result->num_rows;
            }

            if (method_exists($this->result, 'numRows')) {
                return (int) $this->result->numRows();
            }
        }

        return 0;
    }

    public function create(string $query): void
    {
        try {
            $success = $this->conn->multi_query($query);
            if (!$success) {
                throw new MySQLException("Failed to create table(s). Query: " . $query);
            }

            do {
                if ($result = $this->conn->store_result()) {
                    $result->free();
                }
            } while ($this->conn->more_results() && $this->conn->next_result());

        } catch (Throwable $e) {
            throw new MySQLException("Error creating table(s): " . $e->getMessage(), 0, $e);
        }
    }

    public function escape(string $value): string
    {
        return $this->conn->real_escape_string($value);
    }

    public function getInsertId(): int
    {
        return (int) $this->conn->insert_id;
    }

    public function queryArray(): array
    {
        return $this->rows;
    }

    public function mySQLError(): string|bool
    {
        return $this->error;
    }

    public function getConn(): object
    {
        return $this->conn;
    }

    public function setConn(object $conn): void
    {
        $this->conn = $conn;
    }

    public function getResult(): object|bool|null
    {
        return $this->result;
    }

    public function setResult(object|bool|null $result): void
    {
        $this->result = $result;
    }

    public function isError(): bool
    {
        return (bool)$this->error;
    }

    public function setError(bool|string $error): void
    {
        $this->error = $error;
    }

    public function getRows(): array
    {
        return $this->rows;
    }

    public function setRows(array $rows): void
    {
        $this->rows = $rows;
    }

    public function getRow(): ?array
    {
        return $this->row;
    }

    public function setRow(?array $row): void
    {
        $this->row = $row;
    }

    public function isStatus(): bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): void
    {
        $this->status = $status;
    }
}
