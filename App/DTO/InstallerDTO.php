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
        $this->dbhost = $request->post('dbhost') ?? '';
        $this->dbuser = $request->post('dbuser') ?? '';
        $this->dbpass = $request->post('dbpass') ?? '';
        $this->dbname = $request->post('dbname') ?? '';
        $this->title = $request->post('title') ?? '';
        $this->url = $request->post('url') ?? '';
        $this->description = $request->post('description') ?? '';
        $this->keywords = $request->post('keywords') ?? '';
        $this->basepath = $request->post('basepath') ?? '';
        $this->username = $request->post('username') ?? '';
        $this->passwd = $request->post('passwd') ?? '';
        $this->passwd2 = $request->post('passwd2') ?? '';
        $this->firstname = $request->post('firstname') ?? '';
        $this->lastname = $request->post('lastname') ?? '';
        $this->email = $request->post('email') ?? '';

        return $this;
    }

}
