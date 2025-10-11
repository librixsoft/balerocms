<?php

namespace Framework\Utils;

class AdminFlash
{
    private const FLASH_KEY = '_admin_flash';

    public function __construct()
    {
        $this->ensureSessionStarted();
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[self::FLASH_KEY][$key] = $value;
    }

    private function ensureSessionStarted(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[self::FLASH_KEY][$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[self::FLASH_KEY][$key]);
    }

    public function clear(): void
    {
        unset($_SESSION[self::FLASH_KEY]);
    }

    public function delete(string $flatKey): void
    {
        if (!isset($_SESSION[self::FLASH_KEY])) {
            return;
        }

        $keys = explode('.', $flatKey);
        $ref =& $_SESSION[self::FLASH_KEY];

        if (count($keys) === 1 && isset($ref[$flatKey])) {
            unset($ref[$flatKey]);
            return;
        }

        foreach ($keys as $k) {
            if (isset($ref[$k])) {
                if ($k === end($keys)) {
                    unset($ref[$k]);
                } else {
                    $ref =& $ref[$k];
                }
            } else {
                break;
            }
        }
    }
}