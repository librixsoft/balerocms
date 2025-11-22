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

}
