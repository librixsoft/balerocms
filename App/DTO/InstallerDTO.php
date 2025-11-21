<?php

namespace App\DTO;

use Framework\Attributes\Validation\Email;
use Framework\Attributes\Validation\NotEmpty;
use Framework\Attributes\Validation\FieldMatch;
use Framework\DTO\Attributes\Getter;
use Framework\DTO\Attributes\Setter;
use Framework\DTO\Attributes\ToArray;
use Framework\Http\RequestHelper;

#[Getter]
#[Setter]
#[ToArray]
class InstallerDTO
{
    // --- DATABASE CONFIG ---
    #[NotEmpty(message: 'Database host cannot be empty.')]
    public ?string $dbhost = null;

    #[NotEmpty(message: 'Database username cannot be empty.')]
    public ?string $dbuser = null;

    // Contraseña puede estar vacía (muchos entornos locales no usan)
    public ?string $dbpass = null;

    #[NotEmpty(message: 'Database name cannot be empty.')]
    public ?string $dbname = null;

    // --- SITE INFO ---
    #[NotEmpty(message: 'Base path cannot be empty.')]
    public ?string $basepath = null;

    #[NotEmpty(message: 'Site title cannot be empty.')]
    public ?string $title = null;

    #[NotEmpty(message: 'Site URL cannot be empty.')]
    public ?string $url = null;

    public ?string $keywords = null;
    public ?string $description = null;

    // --- ADMIN CONFIG ---
    #[NotEmpty(message: 'Username cannot be empty.')]
    public ?string $username = null;

    #[NotEmpty(message: 'Password cannot be empty.')]
    public ?string $passwd = null;

    #[FieldMatch(field: 'passwd', message: 'Passwords do not match.')]
    public ?string $passwd2 = null;

    #[Email(message: 'Invalid email address.')]
    public ?string $email = null;

    #[NotEmpty(message: 'First name cannot be empty.')]
    public ?string $firstname = null;

    #[NotEmpty(message: 'Last name cannot be empty.')]
    public ?string $lastname = null;

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
