<?php

namespace Framework\Static;

class Flash
{
    private const FLASH_KEY = '_flash';

    /**
     * Guarda un valor en la sesión flash
     */
    public static function set(string $key, mixed $value): void
    {
        self::ensureSessionStarted();
        $_SESSION[self::FLASH_KEY][$key] = $value;
    }

    /**
     * Asegura que la sesión esté iniciada
     */
    private static function ensureSessionStarted(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Obtiene un valor flash (y lo elimina)
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::ensureSessionStarted();
        //var_dump($_SESSION[self::FLASH_KEY]);
        return $_SESSION[self::FLASH_KEY][$key] ?? $default;
    }

    /**
     * Verifica si existe un valor flash
     */
    public static function has(string $key): bool
    {
        self::ensureSessionStarted();
        return isset($_SESSION[self::FLASH_KEY][$key]);
    }

    /**
     * Limpia todos los valores flash
     */
    public static function clear(): void
    {
        self::ensureSessionStarted();
        unset($_SESSION[self::FLASH_KEY]);
    }

    /**
     * Elimina un valor flash específico usando clave aplanada y clave simple
     * Ej: 'errors.username' o 'campo'
     */
    public static function delete(string $flatKey): void
    {
        self::ensureSessionStarted();

        if (!isset($_SESSION[self::FLASH_KEY])) {
            return;
        }

        $keys = explode('.', $flatKey);
        $ref =& $_SESSION[self::FLASH_KEY];

        // 🔹 Cambio relevante: si la clave es simple y existe, la eliminamos directamente
        if (count($keys) === 1 && isset($ref[$flatKey])) {
            unset($ref[$flatKey]);
            return;
        }

        // Mantener compatibilidad con arrays anidados
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
