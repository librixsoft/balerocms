<?php


namespace App\Mapper;

use App\DTO\SettingsDTO;
use Framework\Core\ConfigSettings;

class AdminSettingsMapper
{

    public function mapAndSaveSettings(SettingsDTO $dto, ConfigSettings $config): void
    {
        $config->title       = $dto->title;
        $config->description = $dto->description;
        $config->keywords    = $dto->keywords;
        $config->debug       = $dto->debug;
        $config->url         = $dto->url;
        $config->theme       = $dto->theme;
        $config->language    = $dto->language;
        $config->footer      = $dto->footer;
    }

}
