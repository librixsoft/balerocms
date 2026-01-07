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
use mysqli_result;
use Throwable;

class MySQL
{
    private ConfigSettings $config;
    private mysqli $conn;

    /**
     * Puede ser mysqli_result o false o null (al inicio)
     */
    private mysqli_result|bool|null $result = null;

    private bool|string $error = false;
    private array $rows = [];
    private ?array $row = null;

    private bool $status = false;

    public function connect(string $host, string $user, string $pass, ?string $dbname = null): void
    {
        if ($dbname) {
            $this->conn = @new mysqli($host, $user, $pass, $dbname);
        } else {
            $this->conn = @new mysqli($host, $user, $pass);
        }

        $this->status = !$this->conn->connect_error;
        if (!$this->status) {
            throw new MySQLException("Failed to connect to MySQL: " . $this->conn->connect_error);
        }

        if (!$this->conn->set_charset("utf8mb4")) {
            throw new MySQLException("Error loading character set utf8mb4: " . $this->conn->error);
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
                $stmt->bind_param($types, ...$params);

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
            if (!($this->result instanceof mysqli_result)) {
                throw new MySQLException("No valid result set available for fetching.");
            }

            $this->rows = [];
            while ($row = $this->result->fetch_array(MYSQLI_ASSOC)) {
                $this->rows[] = $row;
            }

            $this->row = $this->rows[0] ?? null;

        } catch (Throwable $e) {
            throw new MySQLException("Error while fetching query result: " . $e->getMessage(), 0, $e);
        }
    }

    public function num_rows(): int
    {
        if ($this->result instanceof mysqli_result) {
            return $this->result->num_rows;
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

    public function queryArray(): array
    {
        return $this->rows;
    }

    public function mySQLError(): string|bool
    {
        return $this->error;
    }

    public function getConn(): mysqli
    {
        return $this->conn;
    }

    public function setConn(mysqli $conn): void
    {
        $this->conn = $conn;
    }

    public function getResult(): mysqli_result|bool|null
    {
        return $this->result;
    }

    public function setResult(mysqli_result|bool|null $result): void
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
