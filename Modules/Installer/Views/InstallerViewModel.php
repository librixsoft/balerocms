<?php

namespace Modules\Installer\Views;

use Framework\Core\ConfigSettings;
use Framework\Core\ViewModel;
use Framework\Static\Constant;

class InstallerViewModel
{
    private ConfigSettings $config;
    private ViewModel $viewModel;

    public function __construct(ConfigSettings $config)
    {
        $this->config = $config;
        $this->viewModel = new ViewModel(); // instanciación interna
    }

    /**
     * Pobla todos los parámetros de instalación y devuelve el array listo para render
     */
    public function setInstallerParams(array $extraParams = []): array
    {

        $session_lang = $_SESSION['lang'] ?? 'en';
        $lang_selected_en = $session_lang === 'en' ? 'selected' : '';
        $lang_selected_es = $session_lang === 'es' ? 'selected' : '';

        $this->viewModel->addAll([

            // Botones
            'btn_save' => '{installer.save}',
            'btn_install' => '{installer.install}',

            // Valores configurables
            'txt_dbhost' => $this->config->dbhost,
            'txt_dbuser' => $this->config->dbuser,
            'txt_dbpass' => $this->config->dbpass,
            'txt_dbname' => $this->config->dbname,
            'txt_basepath' => $this->config->basepath,
            'txt_url' => $this->config->url,
            'username' => $this->config->username,
            'txt_pass' => '',
            'txt_retype' => '',
            'txt_firstname' => $this->config->firstname,
            'txt_lastname' => $this->config->lastname,
            'txt_email' => $this->config->email,

            // Setup Wizard
            'mod_rewrite_enabled' => function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules()),
            'config_writeable' => is_writable($this->config->getConfigPath()),

            'lang_selected_en' => $lang_selected_en,
            'lang_selected_es' => $lang_selected_es,

        ]);

        // Obtenemos flags que llegan del Controller o calculamos localmente
        // NOTA: el flag db_ok lo pasamos desde el Controller, si no está asumimos false
        $dbOk = $extraParams['db_ok'] ?? false;

        // Estado del sistema
        $fieldsValid = $this->areFieldsValid($extraParams);
        $modRewrite = function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules());
        $configWritable = is_writable($this->config->getConfigPath());

        // Agregar estado como flags
        $this->viewModel->addAll([
            'fields_valid' => $fieldsValid,
            'db_ok' => $dbOk,
            'mod_rewrite_enabled' => $modRewrite,
            'config_writable' => $configWritable,
        ]);

        // Merge con parámetros extra (por ejemplo errores o datos cacheados)
        if (!empty($extraParams)) {
            $this->viewModel->addAll($extraParams);
        }

        return $this->viewModel->all();
    }

    private function areFieldsValid(array $extraParams = []): bool
    {
        if (!empty($extraParams['errors'])) {
            return false;
        }

        return
            $this->config->dbhost &&
            $this->config->dbuser &&
            $this->config->dbname &&
            $this->config->basepath &&
            $this->config->url &&
            $this->config->username &&
            $this->config->firstname &&
            $this->config->lastname &&
            $this->config->email &&
            filter_var($this->config->email, FILTER_VALIDATE_EMAIL);
    }

}
