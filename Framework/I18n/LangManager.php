<?php

namespace Framework\I18n;

class LangManager
{
    public array $translations = [];
    private string $currentLang = 'en';

    public function load(string $lang, string $path): void
    {
        $this->translations = [];
        $this->currentLang = $lang;
        $dir = rtrim($path, '/') . "/$lang";

        if (!is_dir($dir)) {
            return;
        }

        foreach (glob("$dir/*.php") as $file) {
            $filename = basename($file, '.php');
            $translations = require $file;
            if (is_array($translations)) {
                $this->translations[$filename] = $translations;
            }
        }
    }

    public function get(string $fullKey, string $default = ''): string
    {
        $parts = explode('.', $fullKey, 2);
        if (count($parts) === 2) {
            [$file, $key] = $parts;
            return $this->translations[$file][$key] ?? $default;
        }
        return $this->translations[$fullKey] ?? $default;
    }

    public function current(): string
    {
        return $this->currentLang;
    }

    public function setCurrentLang(string $lang): void
    {
        $this->currentLang = $lang;
    }


}
