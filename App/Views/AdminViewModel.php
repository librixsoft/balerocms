<?php

namespace App\Views;

use Framework\Core\ConfigSettings;
use Framework\Core\ThemesReader;
use Framework\Core\ViewModel;
use Framework\I18n\Translator;

class AdminViewModel
{
    private ConfigSettings $config;
    private ThemesReader $themesReader;
    private Translator $translator;

    public function __construct(
        ConfigSettings $config,
        ThemesReader $themesReader,
        Translator $translator
    )
    {
        $this->config = $config;
        $this->themesReader = $themesReader;
        $this->translator = $translator;
    }

    public function getSettingsParams(array $extraParams = []): array
    {
        $viewModel = $this->createViewModel();

        $currentLang = $this->config->language ?? 'en';

        include_once $_SERVER['DOCUMENT_ROOT'] . '/version.php';

        $viewModel->addAll([

            'mod_name' => $this->translator->t("admin.settings"),
            'mod_id' => 'settings',

            'core_version' => _CORE_VERSION,

            'debugOptions' => ['dev' => 'Development', 'prod' => 'Production'],
            'debug' => $this->config->debug,

            'defaultTheme' => $this->config->theme,
            'themes' => $this->themesReader->getThemes(),

            'activeMenu' => 'settings',

            'lbl_theme' => "Theme",
            'lbl_settings' => '{admin.settings}',
            'lbl_title' => 'Title',
            'lbl_keywords' => 'Keywords',
            'lbl_description' => 'Description',
            'lbl_footer' => 'Footer',

            'lbl_language' => 'Default System Language',
            'languages' => ['en' => 'English', 'es' => 'Español'],
            'defaultLanguage' => $currentLang,

            'txt_title' => $this->config->title,
            'txt_keywords' => $this->config->keywords,
            'txt_url' => $this->config->url,
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

        $session_lang = $_SESSION['lang'] ?? 'en';
        $lang_selected_en = $session_lang === 'en' ? 'selected' : '';
        $lang_selected_es = $session_lang === 'es' ? 'selected' : '';

        // Parámetros base disponibles en todas las vistas
        $viewModel->addAll([
            'username' => $this->config->username,
            'email' => $this->config->email,
            'lang_selected_en' => $lang_selected_en,
            'lang_selected_es' => $lang_selected_es,
        ]);

        return $viewModel;
    }

    public function getPagesParams(array $extraParams = []): array
    {
        $viewModel = $this->createViewModel();

        $viewModel->addAll([

            'mod_name' => $this->translator->t("admin.pages"),
            'mod_id' =>'page_new',

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
            'mod_name' => $this->translator->t("admin.pages"),
            'mod_id' =>'all_pages',
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
            'mod_name' => $this->translator->t("admin.pages"),
            'activeMenu' => 'all_pages',
            'mod_id' => 'page_edit',
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
            'mod_name' => $this->translator->t("admin.blocks"),
            'mod_id' => 'all_blocks',
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
            'mod_name' => $this->translator->t("admin.blocks"),
            'lbl_new_block' => 'New Block',
            'activeMenu' => 'all_blocks',
            'mod_id' => 'block_new',
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
            'mod_name' => $this->translator->t("admin.blocks"),
            'lbl_edit_block' => 'Edit Block',
            'activeMenu' => 'all_blocks',
            'mod_id' => 'block_edit',
        ]);

        if (!empty($extraParams)) {
            $viewModel->addAll($extraParams);
        }

        return $viewModel->all();
    }

    public function getUpdateParams(array $extraParams = []): array
    {
        $viewModel = $this->createViewModel();

        $viewModel->addAll([
            'mod_name' => 'System Update',
            'mod_id' => 'update',
            'activeMenu' => 'update',
        ]);

        if (!empty($extraParams)) {
            $viewModel->addAll($extraParams);
        }

        return $viewModel->all();
    }

    public function getMediaParams(array $extraParams = []): array
    {
        $viewModel = $this->createViewModel();

        $viewModel->addAll([
            'mod_name' => $this->translator->t("admin.media") ?? 'Media',
            'mod_id' => 'media',
            'activeMenu' => 'media',
        ]);

        if (!empty($extraParams)) {
            $viewModel->addAll($extraParams);
        }

        return $viewModel->all();
    }

    public function getThemesParams(array $extraParams = []): array
    {
        $viewModel = $this->createViewModel();

        $themeDirs = $this->themesReader->getThemes();
        $themesList = [];

        foreach ($themeDirs as $themeDir) {
            $cssPath = rtrim(BASE_PATH, '/') . '/public/assets/themes/' . $themeDir . '/style.css';
            
            $primaryColor = '#1e3c72';
            $textColor = '#343a40';
            $headerColor = '#1e3c72';
            
            if (file_exists($cssPath)) {
                $cssContent = file_get_contents($cssPath);
                
                // Extraer el color de texto del body
                if (preg_match('/body[^{]*{[^}]*color:\s*(#[0-9a-fA-F]{3,6}|rgba?\([^)]+\))/si', $cssContent, $m)) {
                    $textColor = $m[1];
                }
                
                // Extraer color de los encabezados (h1, h2, etc)
                if (preg_match('/h[1-6][^{]*{[^}]*color:\s*(#[0-9a-fA-F]{3,6}|rgba?\([^)]+\))/si', $cssContent, $m)) {
                    $headerColor = $m[1];
                }
                
                // Extraer el fondo primario / hero header
                if (preg_match('/\.hero-header[^{]*{[^}]*background:\s*([^;]+);/si', $cssContent, $m)) {
                    $primaryColor = trim($m[1]);
                } elseif (preg_match('/background:\s*(linear-gradient[^;]+);/si', $cssContent, $m)) {
                    $primaryColor = trim($m[1]);
                }
            }

            $themesList[] = [
                'id' => $themeDir,
                'name' => ucfirst($themeDir),
                'primary_color' => str_replace('"', "'", $primaryColor), // sanitize for html attr
                'text_color' => $textColor,
                'header_color' => $headerColor,
            ];
        }

        $viewModel->addAll([
            'mod_name' => $this->translator->t("admin.themes") ?? 'Themes',
            'mod_id' => 'themes',
            'activeMenu' => 'themes',
            'themes_list' => $themesList,
            'current_theme' => $this->config->theme,
        ]);

        if (!empty($extraParams)) {
            $viewModel->addAll($extraParams);
        }

        return $viewModel->all();
    }
}
