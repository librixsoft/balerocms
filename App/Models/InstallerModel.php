<?php

namespace App\Models;

use Framework\Attributes\Inject;
use Framework\Core\ConfigSettings;
use Framework\Exceptions\ModelException;
use Throwable;
use Framework\Core\Model;

class InstallerModel
{
    private string $tablesSqlPath = BASE_PATH . '/App/sql/tables.sql';

    #[Inject]
    private Model $model;

    #[Inject]
    private ConfigSettings $configSettings;

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
                throw new ModelException("Unable to connect to or create the database.");
            }

            // Reconnect using the database
            $this->model->getDb()->connect($host, $user, $pass, $dbname);

            // Load and execute the SQL file
            $sqlFile = $this->getTablesSqlPath();

            if (!file_exists($sqlFile)) {
                throw new ModelException("SQL file not found: $sqlFile");
            }

            $query = file_get_contents($sqlFile);
            if ($query === false) {
                throw new ModelException("Unable to read SQL file: $sqlFile");
            }

            $query = str_replace("{dbname}", $dbname, $query);
            $this->model->getDb()->create($query);

        } catch (Throwable $e) {
            $this->configSettings->installed = "no";
            throw new ModelException(
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
            $this->model->getDb()->connect($host, $user, $pass);

            if (!$this->model->getDb()->isStatus()) {
                return false;
            }

            // Create database if it does not exist
            $this->model->getDb()->query("CREATE DATABASE IF NOT EXISTS `$dbname`;");

            // Reconnect using the database
            $this->model->getDb()->connect($host, $user, $pass, $dbname);

            return $this->model->getDb()->isStatus();
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
