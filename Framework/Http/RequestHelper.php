<?php

namespace Framework\Http;

use Framework\Security\Security;

class RequestHelper
{
    private Security $security;

    public function __construct()
    {
        $this->security = new Security();
    }

    public function get($key, $default = null)
    {
        return $this->filter($_GET[$key] ?? $default);
    }

    protected function filter($value)
    {
        if ($value === null) {
            return null;
        }

        return $this->security->sanitize($value);
    }

    public function post($key, $default = null)
    {
        return $this->filter($_POST[$key] ?? $default);
    }

    public function hasPost($key): bool
    {
        return isset($_POST[$key]);
    }

    public function hasGet($key): bool
    {
        return isset($_GET[$key]);
    }

    /**
     * Leer cookie segura
     */
    public function cookie($key, $default = null)
    {
        return $this->filter($_COOKIE[$key] ?? $default);
    }

    /**
     * Verificar existencia de cookie
     */
    public function hasCookie($key): bool
    {
        return isset($_COOKIE[$key]);
    }

    public function raw($key, $default = null)
    {
        $value = $_POST[$key] ?? $default;
        if ($value === null) {
            return null;
        }
        return $this->security->antiXSS($value);
    }

    public function getPath(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        return rtrim($path, '/'); // elimina el slash final
    }

}
