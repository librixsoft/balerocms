<?php

/**
 * Balero CMS
 * @author Anibal Gomez <balerocms@gmail.com>
 * @license GNU General Public License
 */

namespace Framework\I18n;

class LangManager
{
    private static array $translations = [];
    private static string $currentLang = 'en';

    public static function load(string $lang, string $path): void
    {
        self::$translations = [];
        self::$currentLang = $lang;
        $dir = rtrim($path, '/') . "/$lang";

        if (!is_dir($dir)) {
            return;
        }

        foreach (glob("$dir/*.php") as $file) {
            $filename = basename($file, '.php');
            $translations = require $file;
            if (is_array($translations)) {
                self::$translations[$filename] = $translations;
            }
        }
    }

    public static function get(string $key, string $default = ''): string
    {
        $parts = explode('.', $key, 2);

        if (count($parts) === 2) {
            [$fileKey, $nestedKey] = $parts;
            return self::$translations[$fileKey][$nestedKey] ?? $default;
        }

        return self::$translations[$key] ?? $default;
    }

    public static function current(): string
    {
        return self::$currentLang;
    }
}
