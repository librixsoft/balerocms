<?php

namespace Framework\Services;

use Framework\Core\ConfigSettings;

class RedirectService
{
    protected ConfigSettings $config;

    public function __construct(ConfigSettings $config)
    {
        $this->config = $config;
    }

    public function to(string $url, bool $forceExit = true): void
    {
        $basepath = rtrim($this->config->basepath, '/');
        $url = ltrim($url, '/');
        $normalizedUrl = preg_replace('#(?<!:)//+#', '/', $basepath . '/' . $url);

        header("Location: " . $normalizedUrl);

        if ($forceExit) {
            exit;
        }
    }
}
