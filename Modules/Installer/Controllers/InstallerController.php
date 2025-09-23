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

        // Recolectar errores y datos cacheados
        $params = [];

        if (Flash::has('errors')) {
            $params['errors'] = Flash::get('errors');
        }

        // Obtener el flag de conexión DB desde el modelo
        $dbOk = $this->model->canConnectToDatabase();

        // Pasar ese dato al ViewModel en extraParams
        $params['db_ok'] = $dbOk;

        // Obtener todos los parámetros del instalador listos para render
        $params = $this->installerViewModel->setInstallerParams($params);

        return $this->render("installer/setup_wizard.html", $params, false);
    }

    #[Post('/install')]
    public function postInstall()
    {
        $installerDTO = InstallerDTO::fromRequest($this->request);
        $input = (array)$installerDTO;

        $validator = Validator::make($input)
            ->required('username', 'El nombre de usuario no puede estar vacío.')
            ->required('passwd', 'La contraseña no puede estar vacía.')
            ->match('passwd', 'passwd2', 'Las contraseñas no coinciden.')
            ->email('email', 'El correo electrónico no es válido.');

        if ($validator->fails()) {
            Flash::set('errors', $validator->errors());
        } else {
            InstallerMapper::map($installerDTO, $this->configSettings);
        }

        // PRG
        Redirect::to("/installer/");
    }

    #[Get('progressBar')]
    public function getProgressBar()
    {
        if (!Flash::has('install_in_progress')) {
            Redirect::to("/installer/");
        }
        $this->model->setInstalled();
        $params = $this->installerViewModel->setInstallerParams();
        Flash::delete('install_in_progress');
        return $this->render("installer/progressBar.html", $params, false);
    }

    #[Post('progressBar')]
    public function postProgressBar()
    {
        Flash::set('install_in_progress', true);
        $this->model->install();
        Redirect::to("/installer/progressBar");
    }

}
