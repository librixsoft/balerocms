<?php

namespace Framework\Static;

use Framework\Services\RedirectService;

class Redirect
{
    protected static ?RedirectService $instance = null;

    public static function setInstance(RedirectService $service): void
    {
        self::$instance = $service;
    }

    public static function to(string $url): void
    {
        if (!self::$instance) {
            throw new \RuntimeException("Redirect instance not set.");
        }

        self::$instance->to($url, true);
    }
}
