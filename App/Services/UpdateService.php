<?php

namespace App\Services;

use Framework\Attributes\Service;

#[Service]
class UpdateService
{
    private const REPO_URL = 'https://raw.githubusercontent.com/librixsoft/balerocms/development/public/version.php';
    private const GITHUB_REPO = 'https://github.com/librixsoft/balerocms/tree/development';

    public function getCurrentVersion(): string
    {
        if (!defined('_CORE_VERSION')) {
             $path = $_SERVER['DOCUMENT_ROOT'] . '/version.php';
             if (file_exists($path)) {
                include_once $path;
             }
             if (!defined('_CORE_VERSION')) {
                 return 'Unknown';
             }
        }
        return _CORE_VERSION;
    }

    public function getRemoteVersion(): ?string
    {
        // Set user agent to avoid 403 forbidden from GitHub
        $options = [
            'http' => [
                'header' => "User-Agent: BaleroCMS-Updater\r\n"
            ]
        ];
        $context = stream_context_create($options);
        
        $content = @file_get_contents(self::REPO_URL, false, $context);
        if ($content === false) {
            return null;
        }

        if (preg_match('/const _CORE_VERSION = "(.*?)";/', $content, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public function isUpdateAvailable(): array
    {
        $current = $this->getCurrentVersion();
        $remote = $this->getRemoteVersion();
        $updateAvailable = false;

        if ($remote && version_compare($remote, $current, '>')) {
            $updateAvailable = true;
        }

        return [
            'current_version' => $current,
            'remote_version' => $remote ?? 'Unknown',
            'update_available' => $updateAvailable,
            'repo_url' => self::GITHUB_REPO
        ];
    }
}
