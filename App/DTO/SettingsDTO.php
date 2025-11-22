<?php

namespace App\DTO;

use Framework\Attributes\DTO;
use Framework\Attributes\Validation\NotEmpty;
use Framework\DTO\Attributes\Getter;
use Framework\DTO\Attributes\Setter;
use Framework\DTO\Attributes\ToArray;
use Framework\Http\RequestHelper;

#[DTO]
#[Getter]
#[Setter]
#[ToArray]
class SettingsDTO
{
    #[NotEmpty(message: 'El título es requerido.')]
    public ?string $title = null;
    public ?string $description = null;
    public ?string $keywords = null;
    public ?string $debug = null;
    public ?string $url = null;
    public ?string $theme = null;
    public ?string $language = null;
    public ?string $footer = null;

    public function fromRequest(RequestHelper $requestHelper): void
    {
        $this->title = $requestHelper->post('title');
        $this->description = $requestHelper->post('description');
        $this->debug = $requestHelper->post('debug');
        $this->keywords = $requestHelper->post('keywords');
        $this->url = $requestHelper->post('url');
        $this->theme = $requestHelper->post('theme');
        $this->language = $requestHelper->post('language');
        $this->footer = $requestHelper->post('footer');
    }
}