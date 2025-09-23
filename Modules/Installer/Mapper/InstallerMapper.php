<?php

/**
 * Balero CMS
 * @author Anibal Gomez <balerocms@gmail.com>
 * @license GNU General Public License
 */

namespace Modules\Installer\Mapper;

use Framework\Core\ConfigSettings;
use Framework\Static\Hash;
use Modules\Installer\DTO\InstallerDTO;

class InstallerMapper
{
    public static function map(InstallerDTO $dto, ConfigSettings $config): void
    {
        // Database
        $config->dbhost = $dto->dbhost;
        $config->dbuser = $dto->dbuser;
        $config->dbpass = $dto->dbpass;
        $config->dbname = $dto->dbname;

        // Site
        $config->title = $dto->title;
        $config->url = $dto->url;
        $config->description = $dto->description;
        $config->keywords = $dto->keywords;
        $config->basepath = $dto->basepath ?: $config->getFullBasepath();

        // Admin
        $config->lastname = $dto->lastname;
        $config->firstname = $dto->firstname;
        $config->username = $dto->username;
        $config->email = $dto->email;

        // Password
        $config->pass = Hash::genpwd($dto->passwd);
    }
}
