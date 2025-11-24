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

    // Auto-generated methods by cache_dtos.php
    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $value): self
    {
        $this->title = $value;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $value): self
    {
        $this->description = $value;
        return $this;
    }

    public function getKeywords(): ?string
    {
        return $this->keywords;
    }

    public function setKeywords(?string $value): self
    {
        $this->keywords = $value;
        return $this;
    }

    public function getDebug(): ?string
    {
        return $this->debug;
    }

    public function setDebug(?string $value): self
    {
        $this->debug = $value;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $value): self
    {
        $this->url = $value;
        return $this;
    }

    public function getTheme(): ?string
    {
        return $this->theme;
    }

    public function setTheme(?string $value): self
    {
        $this->theme = $value;
        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $value): self
    {
        $this->language = $value;
        return $this;
    }

    public function getFooter(): ?string
    {
        return $this->footer;
    }

    public function setFooter(?string $value): self
    {
        $this->footer = $value;
        return $this;
    }

    public function toArray(): array
    {
        $ref = new \ReflectionClass($this);
        $result = [];
        foreach ($ref->getProperties() as $prop) {
            $prop->setAccessible(true);
            $result[$prop->getName()] = $prop->getValue($this);
        }
        return $result;
    }
}
