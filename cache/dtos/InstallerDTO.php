<?php

namespace App\DTO;

use Framework\Attributes\DTO;
use Framework\Attributes\Validation\Email;
use Framework\Attributes\Validation\NotEmpty;
use Framework\Attributes\Validation\FieldMatch;
use Framework\DTO\Attributes\Getter;
use Framework\DTO\Attributes\Setter;
use Framework\DTO\Attributes\ToArray;
use Framework\Http\RequestHelper;

#[DTO]
#[Getter]
#[Setter]
#[ToArray]
class InstallerDTO
{
    // --- DATABASE CONFIG ---
    #[NotEmpty(message: 'Database host cannot be empty.')]
    private ?string $dbhost = null;

    #[NotEmpty(message: 'Database username cannot be empty.')]
    private ?string $dbuser = null;

    // Contraseña puede estar vacía (muchos entornos locales no usan)
    private ?string $dbpass = null;

    #[NotEmpty(message: 'Database name cannot be empty.')]
    private ?string $dbname = null;

    // --- SITE INFO ---
    #[NotEmpty(message: 'Base path cannot be empty.')]
    private ?string $basepath = null;

    #[NotEmpty(message: 'Site title cannot be empty.')]
    private ?string $title = null;

    #[NotEmpty(message: 'Site URL cannot be empty.')]
    private ?string $url = null;

    private ?string $keywords = null;
    private ?string $description = null;

    // --- ADMIN CONFIG ---
    #[NotEmpty(message: 'Username cannot be empty.')]
    private ?string $username = null;

    #[NotEmpty(message: 'Password cannot be empty.')]
    private ?string $passwd = null;

    #[FieldMatch(field: 'passwd', message: 'Passwords do not match.')]
    private ?string $passwd2 = null;

    #[Email(message: 'Invalid email address.')]
    private ?string $email = null;

    #[NotEmpty(message: 'First name cannot be empty.')]
    private ?string $firstname = null;

    #[NotEmpty(message: 'Last name cannot be empty.')]
    private ?string $lastname = null;

    /**
     * Carga todos los valores del instalador desde RequestHelper (POST)
     */
    public function fromRequest(RequestHelper $requestHelper): void
    {
        // --- DB CONFIG ---
        $this->dbhost = $requestHelper->post('dbhost');
        $this->dbuser = $requestHelper->post('dbuser');
        $this->dbpass = $requestHelper->post('dbpass');
        $this->dbname = $requestHelper->post('dbname');

        // --- SITE INFO ---
        $this->basepath = $requestHelper->post('basepath');
        $this->title = $requestHelper->post('title');
        $this->url = $requestHelper->post('url');
        $this->keywords = $requestHelper->post('keywords');
        $this->description = $requestHelper->post('description');

        // --- ADMIN CONFIG ---
        $this->username = $requestHelper->post('username');
        $this->passwd = $requestHelper->post('passwd');
        $this->passwd2 = $requestHelper->post('passwd2');
        $this->email = $requestHelper->post('email');
        $this->firstname = $requestHelper->post('firstname');
        $this->lastname = $requestHelper->post('lastname');
    }


    // Auto-generated methods by cache_dtos.php
    public function getDbhost(): ?string
    {
        return $this->dbhost;
    }

    public function setDbhost(?string $value): self
    {
        $this->dbhost = $value;
        return $this;
    }

    public function getDbuser(): ?string
    {
        return $this->dbuser;
    }

    public function setDbuser(?string $value): self
    {
        $this->dbuser = $value;
        return $this;
    }

    public function getDbpass(): ?string
    {
        return $this->dbpass;
    }

    public function setDbpass(?string $value): self
    {
        $this->dbpass = $value;
        return $this;
    }

    public function getDbname(): ?string
    {
        return $this->dbname;
    }

    public function setDbname(?string $value): self
    {
        $this->dbname = $value;
        return $this;
    }

    public function getBasepath(): ?string
    {
        return $this->basepath;
    }

    public function setBasepath(?string $value): self
    {
        $this->basepath = $value;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $value): self
    {
        $this->title = $value;
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

    public function getKeywords(): ?string
    {
        return $this->keywords;
    }

    public function setKeywords(?string $value): self
    {
        $this->keywords = $value;
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

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $value): self
    {
        $this->username = $value;
        return $this;
    }

    public function getPasswd(): ?string
    {
        return $this->passwd;
    }

    public function setPasswd(?string $value): self
    {
        $this->passwd = $value;
        return $this;
    }

    public function getPasswd2(): ?string
    {
        return $this->passwd2;
    }

    public function setPasswd2(?string $value): self
    {
        $this->passwd2 = $value;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $value): self
    {
        $this->email = $value;
        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $value): self
    {
        $this->firstname = $value;
        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $value): self
    {
        $this->lastname = $value;
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
