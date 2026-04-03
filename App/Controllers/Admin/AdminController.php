<?php

namespace App\Controllers\Admin;

use Framework\Attributes\Controller;
use Framework\Attributes\Inject;
use Framework\Http\Get;
use Framework\Http\Auth;
use Framework\Utils\Redirect;

#[Controller('/admin')]
#[Auth(required: true)]
class AdminController
{
    #[Inject]
    private Redirect $redirect;

    #[Get('/')]
    public function home()
    {
        $this->redirect->to('/admin/settings');
    }

    #[Get('/dashboard')]
    public function dashboard()
    {
        $this->redirect->to('/admin/settings');
    }
}
