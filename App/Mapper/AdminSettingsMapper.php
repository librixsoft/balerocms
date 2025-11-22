<?php


namespace App\Mapper;

use App\DTO\SettingsDTO;
use Framework\Core\ConfigSettings;

class AdminSettingsMapper
{

    public function mapAndSaveSettings(SettingsDTO $dto, ConfigSettings $config): void
    {
        $config->title       = $dto->getTitle();
        $config->description = $dto->getDescription();
        $config->keywords    = $dto->getKeywords();
        $config->debug       = $dto->getDebug();
        $config->url         = $dto->getUrl();
        $config->theme       = $dto->getTheme();
        $config->language    = $dto->getLanguage();
        $config->footer      = $dto->getFooter();
    }

}
