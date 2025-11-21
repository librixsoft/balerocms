<?php


namespace App\Mapper;

use Framework\Core\ConfigSettings;
use Framework\Utils\Hash;
use App\DTO\InstallerDTO;

class InstallerMapper
{

    private Hash $hash;

    public function __construct(Hash $hash)
    {
        $this->hash = $hash;
    }


    public function mapAndSaveSettings(InstallerDTO $dto, ConfigSettings $config): void
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
        $config->pass = $this->hash->genpwd($dto->passwd);
    }
}
