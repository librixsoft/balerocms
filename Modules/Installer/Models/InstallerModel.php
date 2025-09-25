<?php

namespace Modules\Installer\Models;

use Exception;
use Framework\Core\ErrorConsole;
use Framework\Core\Model;
use Throwable;

class InstallerModel extends Model
{

    private string $tablesSqlPath = LOCAL_DIR . "/Modules/Installer/sql/tables.sql";

    /**
     * Ejecuta la instalación de la base de datos y las tablas.
     * Verifica la conexión, asegura que la base de datos exista y ejecuta el SQL del archivo de tablas.
     */
    public function install(): void
    {
        try {
            $host = $this->configSettings->dbhost;
            $user = $this->configSettings->dbuser;
            $pass = $this->configSettings->dbpass;
            $dbname = $this->configSettings->dbname;

            // Verificar conexión y que la base de datos exista
            if (!$this->canConnectToDatabase()) {
                throw new Exception("No se pudo conectar o crear la base de datos.");
            }

            // Reconectar usando la base de datos
            $this->db->connect($host, $user, $pass, $dbname);

            // Cargar y ejecutar el archivo SQL
            $sqlFile = $this->getTablesSqlPath();

            if (!file_exists($sqlFile)) {
                throw new Exception("Archivo SQL no encontrado: $sqlFile");
            }

            $query = file_get_contents($sqlFile);
            if ($query === false) {
                throw new Exception("No se pudo leer el archivo SQL: $sqlFile");
            }

            $query = str_replace("{dbname}", $dbname, $query);
            $this->db->create($query);

        } catch (Throwable $e) {
            $this->configSettings->installed = "no";
            ErrorConsole::handleException(
                new Exception("Installation failed: " . $e->getMessage(), 0, $e)
            );
        }
    }

    /**
     * Verifica la conexión a la base de datos.
     * Si la conexión al servidor es exitosa, crea la base de datos si no existe y se reconecta usando la base de datos.
     *
     * @return bool True si la conexión y la base de datos son accesibles, false en caso contrario.
     */
    public function canConnectToDatabase(): bool
    {
        try {
            $host = $this->configSettings->dbhost;
            $user = $this->configSettings->dbuser;
            $pass = $this->configSettings->dbpass;
            $dbname = $this->configSettings->dbname;

            // Conectar al servidor sin especificar la base de datos
            $this->db->connect($host, $user, $pass);

            if (!$this->db->isStatus()) {
                return false;
            }

            // Crear la base de datos si no existe
            $this->db->query("CREATE DATABASE IF NOT EXISTS `$dbname`;");

            // Reconectar usando la base de datos
            $this->db->connect($host, $user, $pass, $dbname);

            return $this->db->isStatus();
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * @return string
     */
    public function getTablesSqlPath(): string
    {
        return $this->tablesSqlPath;
    }

    /**
     * @param string $tablesSqlPath
     */
    public function setTablesSqlPath(string $tablesSqlPath): void
    {
        $this->tablesSqlPath = $tablesSqlPath;
    }


    /**
     * Marca la instalación como completada.
     */
    public function setInstalled(): void
    {
        $this->configSettings->installed = "yes";
    }

}
