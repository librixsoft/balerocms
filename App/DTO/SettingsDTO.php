<?php

namespace App\DTO;

use Framework\Attributes\Validation\NotEmpty;
use Framework\DTO\Attributes\Getter;
use Framework\DTO\Attributes\Setter;
use Framework\DTO\Attributes\ToArray;
use Framework\Http\RequestHelper;

#[Getter]
#[Setter]
#[ToArray]
class SettingsDTO
{
    #[NotEmpty(message: 'El título es requerido.')]
    protected ?string $title = null;
    protected ?string $description = null;
    protected ?string $keywords = null;
    protected ?string $debug = null;
    protected ?string $url = null;
    protected ?string $theme = null;
    protected ?string $language = null;
    protected ?string $footer = null;

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