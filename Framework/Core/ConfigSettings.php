<?php

namespace Framework\Core;

use Framework\Exceptions\ConfigException;

class ConfigSettings
{
    private string $configPath;
    private ?JSONHandler $handler = null;
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

    public function __construct(string $configPath)
    {
        $this->configPath = $configPath;
    }

    public function getHandler(): JSONHandler
    {
        if ($this->handler === null) {
            if (!file_exists($this->configPath)) {
                throw new ConfigException("File not found: {$this->configPath}");
            }
            $this->handler = new JSONHandler($this->configPath);
            $this->loadSettings();
        }
        return $this->handler;
    }

    public function loadSettings(): void
    {
        foreach ($this->fields as $prop => $path) {
            $this->data[$prop] = $this->getHandler()->get($path);
        }
    }

    public function __get(string $name)
    {
        return $this->data[$name] ?? null;
    }

    public function __set(string $name, string $value)
    {
        if (!isset($this->fields[$name])) {
            throw new ConfigException("Propiedad no existe: $name");
        }
        $this->data[$name] = $value;
        $this->getHandler()->set($this->fields[$name], $value);
    }

    public function getFullBasepath(): string
    {
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $scheme = $https ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        $port = '';
        if (strpos($host, ':') === false) {
            $serverPort = $_SERVER['SERVER_PORT'] ?? null;
            if ($serverPort && $serverPort !== '80' && $serverPort !== '443') {
                $port = ':' . $serverPort;
            }
        }
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '/';
        $dir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        if ($dir === '' || $dir === '.') $dir = '/';
        else $dir .= '/';
        return $scheme . '://' . $host . $port . $dir;
    }

    public function getConfigPath(): string
    {
        return $this->configPath;
    }

    public function setConfigPath(string $configPath): void
    {
        $this->configPath = $configPath;
        $this->handler = null;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
