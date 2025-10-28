<?php

namespace App\DTO;

use Framework\Attributes\Validation\Email;
use Framework\Attributes\Validation\FieldMatch;
use Framework\Attributes\Validation\NotEmpty;
use Framework\Http\RequestHelper;

class InstallerDTO
{
    #[NotEmpty(message: 'Username cannot be empty.')]
    public ?string $username = null;

    #[NotEmpty(message: 'Password cannot be empty.')]
    public ?string $passwd = null;

    #[FieldMatch(field: 'passwd', message: 'Passwords do not match.')]
    public ?string $passwd2 = null;

    #[Email(message: 'Invalid email address.')]
    public ?string $email = null;

    public function fromRequest(RequestHelper $requestHelper): void
    {
        $this->username = $requestHelper->post('username');
        $this->passwd = $requestHelper->post('passwd');
        $this->passwd2 = $requestHelper->post('passwd2');
        $this->email = $requestHelper->post('email');
    }
}