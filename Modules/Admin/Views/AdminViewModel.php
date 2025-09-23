<?php

namespace Modules\Admin\Views;

use Framework\Core\ConfigSettings;
use Framework\Core\ThemesReader;
use Framework\Core\ViewModel;

class AdminViewModel
{
    private ConfigSettings $config;
    private ThemesReader $themesReader;

    public function __construct(ConfigSettings $config, ThemesReader $themesReader)
    {
        $this->config = $config;
        $this->themesReader = $themesReader;
    }

    public function getSettingsParams(array $extraParams = []): array
    {
        $viewModel = $this->createViewModel();

        $currentLang = $_SESSION['lang'] ?? $this->config->language ?? 'en';

        $viewModel->addAll([

            'mod_name' =>__('admin.settings'),

            'core_version' => _CORE_VERSION,
            'defaultTheme' => $this->config->theme,
            'themes' => $this->themesReader->getThemes(),
            'activeMenu' => 'settings',
            'lbl_theme' => "Theme",
            'lbl_settings' => __('admin.settings'),
            'lbl_title' => 'Title',
            'lbl_keywords' => 'Keywords',
            'lbl_description' => 'Description',
            'lbl_footer' => 'Footer',

            'lbl_language' => 'Default System Language',
            'languages' => ['en' => 'English', 'es' => 'Español'],
            'defaultLanguage' => $currentLang,

            'txt_title' => $this->config->title,
            'txt_keywords' => $this->config->keywords,
            'txt_description' => $this->config->description,
            'txt_footer' => $this->config->footer,

            'btn_refresh' => 'Refresh',
        ]);

        if (!empty($extraParams)) {
            $viewModel->addAll($extraParams);
        }

        return $viewModel->all();
    }

    private function createViewModel(): ViewModel
    {
        $viewModel = new ViewModel();

        // Parámetros base disponibles en todas las vistas
        $viewModel->addAll([
            'username' => $this->config->username,
            'email' => $this->config->email,
        ]);

        return $viewModel;
    }

    public function getPagesParams(array $extraParams = []): array
    {
        $viewModel = $this->createViewModel();

        $viewModel->addAll([
            'lbl_title' => 'Title',
            'current_date' => date('Y-m-d H:i:s'),
            'new_page' => 'New page',
            'btn_add' => 'Create',
            'lbl_visible' => 'Visible',
            'activeMenu' => 'all_pages',
        ]);

        if (!empty($extraParams)) {
            $viewModel->addAll($extraParams);
        }

        return $viewModel->all();
    }

    public function getAllPagesParams(array $extraParams = []): array
    {
        $viewModel = $this->createViewModel();

        $viewModel->addAll([
            'activeMenu' => 'all_pages',
        ]);

        if (!empty($extraParams)) {
            $viewModel->addAll($extraParams);
        }

        return $viewModel->all();
    }

    public function getEditPageParams(array $extraParams = []): array
    {
        $viewModel = $this->createViewModel();

        $viewModel->addAll([
            'activeMenu' => 'all_pages',
        ]);

        if (!empty($extraParams)) {
            $viewModel->addAll($extraParams);
        }

        return $viewModel->all();
    }

    public function getAllBlocksParams(array $extraParams = []): array
    {
        $viewModel = $this->createViewModel();

        $viewModel->addAll([
            'lbl_blocks' => 'Blocks',
            'lbl_new_block' => 'New Block',
            'activeMenu' => 'all_blocks',
        ]);

        if (!empty($extraParams)) {
            $viewModel->addAll($extraParams);
        }

        return $viewModel->all();
    }

    public function getNewBlockParams(array $extraParams = []): array
    {
        $viewModel = $this->createViewModel();

        $viewModel->addAll([
            'lbl_new_block' => 'New Block',
            'activeMenu' => 'blocks',
        ]);

        if (!empty($extraParams)) {
            $viewModel->addAll($extraParams);
        }

        return $viewModel->all();
    }

    public function getEditBlockParams(array $extraParams = []): array
    {
        $viewModel = $this->createViewModel();

        $viewModel->addAll([
            'lbl_edit_block' => 'Edit Block',
            'activeMenu' => 'blocks',
        ]);

        if (!empty($extraParams)) {
            $viewModel->addAll($extraParams);
        }

        return $viewModel->all();
    }
}
