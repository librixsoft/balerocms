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
        $config->dbhost = $dto->getDbhost();
        $config->dbuser = $dto->getDbuser();
        $config->dbpass = $dto->getDbpass();
        $config->dbname = $dto->getDbname();

        // Site
        $config->title = $dto->getTitle();
        $config->url = $dto->getUrl();
        $config->description = $dto->getDescription();
        $config->keywords = $dto->getKeywords();
        $config->basepath = $dto->getBasepath() ?: $config->getFullBasepath();

        // Admin
        $config->lastname = $dto->getLastname();
        $config->firstname = $dto->getFirstname();
        $config->username = $dto->getUsername();
        $config->email = $dto->getEmail();

        // Password
        $config->pass = $this->hash->genpwd($dto->getPasswd());
    }
}
