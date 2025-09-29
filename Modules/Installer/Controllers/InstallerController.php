<?php

namespace Modules\Installer\Controllers;

use Framework\Core\Controller;
use Framework\Core\Validator;
use Framework\Http\Get;
use Framework\Http\Post;
use Framework\Static\Flash;
use Framework\Static\Redirect;
use Modules\Installer\DTO\InstallerDTO;
use Modules\Installer\Mapper\InstallerMapper;
use Modules\Installer\Models\InstallerModel;
use Modules\Installer\Views\InstallerViewModel;
use Modules\Installer\Exceptions\InstallerException;

class InstallerController extends Controller
{
    protected InstallerModel $model;
    protected InstallerViewModel $installerViewModel;

    public function __construct(
        InstallerModel $model,
        InstallerViewModel $installerViewModel
    )
    {
        $this->model = $model;
        $this->installerViewModel = $installerViewModel;
    }

    #[Get('/')]
    public function home()
    {
        $params = [];

        if (Flash::has('errors')) {
            $params['errors'] = Flash::get('errors');
        }

        $dbOk = $this->model->canConnectToDatabase();
        $params['db_ok'] = $dbOk;

        $params = $this->installerViewModel->setInstallerParams($params);

        return $this->render("installer/setup_wizard.html", $params, false);
    }

    #[Post('/install')]
    public function postInstall()
    {
        $installerDTO = InstallerDTO::fromRequest($this->request);
        $input = (array)$installerDTO;

        $validator = Validator::make($input)
            ->required('username', 'Username cannot be empty.')
            ->required('passwd', 'Password cannot be empty.')
            ->match('passwd', 'passwd2', 'Passwords do not match.')
            ->email('email', 'Invalid email address.');

        if ($validator->fails()) {
            Flash::set('errors', $validator->errors());
        } else {
            try {
                InstallerMapper::map($installerDTO, $this->configSettings);
            } catch (\Throwable $e) {
                throw new InstallerException(
                    "Failed to map installer data: " . $e->getMessage(),
                    previous: $e
                );
            }
        }

        Redirect::to("/installer/");
    }

    #[Get('progressBar')]
    public function getProgressBar()
    {
        if (!Flash::has('install_in_progress')) {
            Redirect::to("/installer/");
        }

        try {
            $this->model->setInstalled();
        } catch (\Throwable $e) {
            throw new InstallerException(
                "Failed to mark installation as completed: " . $e->getMessage(),
                previous: $e
            );
        }

        $params = $this->installerViewModel->setInstallerParams();
        Flash::delete('install_in_progress');

        return $this->render("installer/progressBar.html", $params, false);
    }

    #[Post('progressBar')]
    public function postProgressBar()
    {
        Flash::set('install_in_progress', true);

        try {
            $this->model->install();
        } catch (\Throwable $e) {
            throw new InstallerException(
                "Installation failed: " . $e->getMessage(),
                previous: $e
            );
        }

        Redirect::to("/installer/progressBar");
    }
}
