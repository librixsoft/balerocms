<?php

/**
 * Balero CMS
 * @author Anibal Gomez <balerocms@gmail.com>
 * @license GNU General Public License
 */

// Modules/Installer/DTO/InstallerDTO.php

namespace App\DTO;

use Framework\Http\RequestHelper;

class InstallerDTO
{
    public string $dbhost = '';
    public string $dbuser = '';
    public string $dbpass = '';
    public string $dbname = '';
    public string $title = '';
    public string $url = '';
    public string $description = '';
    public string $keywords = '';
    public string $basepath = '';
    public string $username = '';
    public string $passwd = '';
    public string $passwd2 = '';
    public string $firstname = '';
    public string $lastname = '';
    public string $email = '';

    public function fromRequest(RequestHelper $request): self
    {
        $dto = new self();

        $dto->dbhost = $request->post('dbhost') ?? '';
        $dto->dbuser = $request->post('dbuser') ?? '';
        $dto->dbpass = $request->post('dbpass') ?? '';
        $dto->dbname = $request->post('dbname') ?? '';
        $dto->title = $request->post('title') ?? '';
        $dto->url = $request->post('url') ?? '';
        $dto->description = $request->post('description') ?? '';
        $dto->keywords = $request->post('keywords') ?? '';
        $dto->basepath = $request->post('basepath') ?? '';
        $dto->username = $request->post('username') ?? '';
        $dto->passwd = $request->post('passwd') ?? '';
        $dto->passwd2 = $request->post('passwd2') ?? '';
        $dto->firstname = $request->post('firstname') ?? '';
        $dto->lastname = $request->post('lastname') ?? '';
        $dto->email = $request->post('email') ?? '';

        return $dto;
    }
}
