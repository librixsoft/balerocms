<?php

namespace App\Controllers;

use Framework\Attributes\Controller;
use Framework\Attributes\FlashStorage;
use Framework\Core\View;
use Framework\Http\Get;
use Framework\Http\Post;
use App\Models\LoginModel;
use App\Views\LoginViewModel;
use Framework\Security\LoginManager;
use Framework\Utils\Flash;
use Framework\Utils\Redirect;

#[Controller('/login')]
class LoginController
{
    private View $view;
    private LoginModel $model;
    private LoginViewModel $viewModel;
    #[FlashStorage('_flash')]
    private Flash $flash;
    private Redirect $redirect;
    private LoginManager $loginManager;

    public function __construct(
        LoginModel $model,
        LoginViewModel $viewModel,
        Flash $flash,
        Redirect $redirect,
        View $view,
        LoginManager $loginManager
    )
    {
        $this->model = $model;
        $this->viewModel = $viewModel;
        $this->flash = $flash;
        $this->redirect = $redirect;
        $this->view = $view;
        $this->loginManager = $loginManager;
    }

    #[Get('/')]
    public function home()
    {
        $params = [];
        if ($this->flash->has('login_error')) {
            $params['login_error'] = $this->flash->get('login_error');
        }
        return $this->view->render("admin/login.html", $params, false);
    }

    #[Post('/')]
    public function login()
    {
        if ($this->loginManager->handleLogin()) {
            $this->redirect->to('/admin/settings');
        } else {
            $error = $this->loginManager->getMessage();
            $this->flash->set('login_error', $error);
            $this->redirect->to('/login/');
        }
    }

    #[Get('/logout')]
    public function logout()
    {
        $this->loginManager->logout();
        $this->redirect->to('/login/');
    }

}
