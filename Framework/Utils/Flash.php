<?php

namespace Framework\Utils;

/**
 * Gestor de mensajes flash unificado
 */
class Flash
{
    private string $flashKey;

    public function __construct(?string $flashKey = null)
    {
        $this->flashKey = $flashKey ?? '_flash';
        $this->ensureSessionStarted();
    }

    private function ensureSessionStarted(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$this->flashKey][$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$this->flashKey][$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$this->flashKey][$key]);
    }

    public function clear(): void
    {
        unset($_SESSION[$this->flashKey]);
    }

    public function delete(string $flatKey): void
    {
        if (!isset($_SESSION[$this->flashKey])) {
            return;
        }

        $keys = explode('.', $flatKey);
        $ref = &$_SESSION[$this->flashKey];

        if (count($keys) === 1 && isset($ref[$flatKey])) {
            unset($ref[$flatKey]);
            return;
        }

        foreach ($keys as $k) {
            if (isset($ref[$k])) {
                if ($k === end($keys)) {
                    unset($ref[$k]);
                } else {
                    $ref = &$ref[$k];
                }
            } else {
                break;
            }
        }
    }
}

