<?php

namespace App\DTO;

use Framework\Attributes\Validation\NotEmpty;
use Framework\Http\RequestHelper;

class SettingsDTO
{
    #[NotEmpty(message: 'El título es requerido.')]
    public ?string $title = null;

    public ?string $description = null;
    public ?string $keywords = null;
    public ?string $theme = null;
    public ?string $language = null;
    public ?string $footer = null;

    public function fromRequest(RequestHelper $requestHelper): void
    {
        $this->title = $requestHelper->post('title');
        $this->description = $requestHelper->post('description');
        $this->keywords = $requestHelper->post('keywords');
        $this->theme = $requestHelper->post('theme');
        $this->language = $requestHelper->post('language');
        $this->footer = $requestHelper->post('footer');
    }
}