<?php

namespace Modules\Installer\Models;

use Throwable;
use Framework\Core\Model;
use Modules\Installer\Exceptions\InstallerException;

class InstallerModel extends Model
{
    private string $tablesSqlPath = LOCAL_DIR . "/Modules/Installer/sql/tables.sql";

    /**
     * Executes the installation of the database and tables.
     */
    public function install(): void
    {
        try {
            $host = $this->configSettings->dbhost;
            $user = $this->configSettings->dbuser;
            $pass = $this->configSettings->dbpass;
            $dbname = $this->configSettings->dbname;

            // Verify connection and that the database exists
            if (!$this->canConnectToDatabase()) {
                throw new InstallerException("Unable to connect to or create the database.");
            }

            // Reconnect using the database
            $this->db->connect($host, $user, $pass, $dbname);

            // Load and execute the SQL file
            $sqlFile = $this->getTablesSqlPath();

            if (!file_exists($sqlFile)) {
                throw new InstallerException("SQL file not found: $sqlFile");
            }

            $query = file_get_contents($sqlFile);
            if ($query === false) {
                throw new InstallerException("Unable to read SQL file: $sqlFile");
            }

            $query = str_replace("{dbname}", $dbname, $query);
            $this->db->create($query);

        } catch (Throwable $e) {
            $this->configSettings->installed = "no";
            throw new InstallerException(
                "Installation failed: " . $e->getMessage(),
                previous: $e
            );
        }
    }

    /**
     * Verifies the database connection.
     */
    public function canConnectToDatabase(): bool
    {
        try {
            $host = $this->configSettings->dbhost;
            $user = $this->configSettings->dbuser;
            $pass = $this->configSettings->dbpass;
            $dbname = $this->configSettings->dbname;

            // Connect to server without specifying a database
            $this->db->connect($host, $user, $pass);

            if (!$this->db->isStatus()) {
                return false;
            }

            // Create database if it does not exist
            $this->db->query("CREATE DATABASE IF NOT EXISTS `$dbname`;");

            // Reconnect using the database
            $this->db->connect($host, $user, $pass, $dbname);

            return $this->db->isStatus();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function getTablesSqlPath(): string
    {
        return $this->tablesSqlPath;
    }

    public function setTablesSqlPath(string $tablesSqlPath): void
    {
        $this->tablesSqlPath = $tablesSqlPath;
    }

    /**
     * Marks the installation as completed.
     */
    public function setInstalled(): void
    {
        $this->configSettings->installed = "yes";
    }
}
