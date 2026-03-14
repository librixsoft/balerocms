<?php

declare(strict_types=1);

namespace App\Models;

function file_exists(string $path): bool
{
    if ($path === 'force-unreadable') {
        return true;
    }

    return \file_exists($path);
}

function file_get_contents(string $path): string|false
{
    if ($path === 'force-unreadable') {
        return false;
    }

    return \file_get_contents($path);
}
