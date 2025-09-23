<?php

namespace Framework\Core;

use Exception;
use Framework\Static\Constant;

class ConfigSettings
{
    private array $fields = [
        // Database
        'dbhost' => '/config/database/dbhost',
        'dbuser' => '/config/database/dbuser',
        'dbpass' => '/config/database/dbpass',
        'dbname' => '/config/database/dbname',

        // Admin
        'username' => '/config/admin/username',
        'pass' => '/config/admin/passwd',
        'email' => '/config/admin/email',
        'firstname' => '/config/admin/firstname',
        'lastname' => '/config/admin/lastname',

        // System
        'installed' => '/config/system/installed',

        // Site
        'language' => '/config/site/language',
        'title' => '/config/site/title',
        'description' => '/config/site/description',
        'url' => '/config/site/url',
        'keywords' => '/config/site/keywords',
        'basepath' => '/config/site/basepath',
        'theme' => '/config/site/theme',
        'footer' => '/config/site/footer',
        'multilang' => '/config/site/multilang',
        'editor' => '/config/site/editor'
    ];

    private array $data = [];

    private JSONHandler $handler;

    /**
     * Constructor flexible
     * @param string|null $file Ruta del JSON de configuración (opcional)
     */
    public function __construct(?string $file = null)
    {
        // Usar JSONHandler como handler por defecto
        $this->handler = new JSONHandler($file ?? Constant::CONFIG_PATH);

        // Cargar todos los valores del JSON
        $this->loadSettings();
    }

    public function loadSettings(): void
    {
        foreach ($this->fields as $prop => $path) {
            $this->data[$prop] = $this->handler->get($path);
        }
    }

    // Magic getter dinámico
    public function __get(string $name)
    {
        return $this->data[$name] ?? null;
    }

    // Magic setter dinámico
    public function __set(string $name, string $value)
    {
        if (!isset($this->fields[$name])) {
            throw new Exception("Propiedad no existe: $name");
        }

        $this->data[$name] = $value;
        $this->handler->set($this->fields[$name], $value);
    }

    public function getFullBasepath(): string
    {
        // Protocolo
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $scheme = $https ? 'https' : 'http';

        // Host (prefiere HTTP_HOST porque puede traer puerto)
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';

        // Si HTTP_HOST ya trae puerto, no añadimos otro; si no, lo añadimos solo si no es 80/443
        $port = '';
        if (strpos($host, ':') === false) {
            $serverPort = $_SERVER['SERVER_PORT'] ?? null;
            if ($serverPort && $serverPort !== '80' && $serverPort !== '443') {
                $port = ':' . $serverPort;
            }
        }

        // Ruta del script (ej: /balerocms-src/public/index.php)
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '/';
        // Normalizar separadores y sacar directorio
        $dir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

        // Construir base con slash final
        if ($dir === '' || $dir === '.') {
            $dir = '/';
        } else {
            $dir .= '/';
        }

        return $scheme . '://' . $host . $port . $dir;
    }

}
