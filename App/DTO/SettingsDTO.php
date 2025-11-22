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
    private ?string $title = null;
    private ?string $description = null;
    private ?string $keywords = null;
    private ?string $debug = null;
    private ?string $url = null;
    private ?string $theme = null;
    private ?string $language = null;
    private ?string $footer = null;

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