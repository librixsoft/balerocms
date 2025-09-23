<?php

namespace Framework\Core;

class ThemesReader
{
    private string $themesPath;

    public function __construct()
    {
        $this->themesPath = rtrim(LOCAL_DIR . '/resources/views/themes', DIRECTORY_SEPARATOR);
    }

    public function getThemes(): array
    {
        $themes = [];
        if (!is_dir($this->themesPath)) return $themes;

        foreach (scandir($this->themesPath) as $dir) {
            if ($dir === '.' || $dir === '..') continue;
            if (is_dir($this->themesPath . DIRECTORY_SEPARATOR . $dir)) {
                $themes[] = $dir;
            }
        }
        return $themes;
    }
}
