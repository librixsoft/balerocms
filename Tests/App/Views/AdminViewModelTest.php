<?php

declare(strict_types=1);

namespace Tests\App\Views;

use App\Views\AdminViewModel;
use Framework\Core\ConfigSettings;
use Framework\Core\ThemesReader;
use Framework\I18n\Translator;
use PHPUnit\Framework\TestCase;

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 3));
}

final class AdminViewModelTest extends TestCase
{
    public function testMainParamBuildersReturnExpectedKeys(): void
    {
        $_SESSION['lang'] = 'en';
        $_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__, 3) . '/public';

        $cfg = $this->createMock(ConfigSettings::class);
        $cfg->language = 'en';
        $cfg->debug = 'dev';
        $cfg->theme = 'default';
        $cfg->title = 'Site';
        $cfg->keywords = 'k';
        $cfg->url = 'u';
        $cfg->description = 'd';
        $cfg->footer = 'f';
        $cfg->username = 'admin';
        $cfg->email = 'a@b.com';

        $themes = $this->createMock(ThemesReader::class);
        $themes->method('getThemes')->willReturn(['default']);

        $tr = $this->createMock(Translator::class);
        $tr->method('t')->willReturnCallback(fn(string $k) => strtoupper($k));

        $vm = new AdminViewModel($cfg, $themes, $tr);

        $settings = $vm->getSettingsParams(['extra' => 1]);
        $this->assertSame('settings', $settings['mod_id']);
        $this->assertSame(1, $settings['extra']);

        $this->assertSame('all_pages', $vm->getAllPagesParams()['mod_id']);
        $this->assertSame('page_edit', $vm->getEditPageParams()['mod_id']);
        $this->assertSame('all_blocks', $vm->getAllBlocksParams()['mod_id']);
        $this->assertSame('block_new', $vm->getNewBlockParams()['mod_id']);
        $this->assertSame('block_edit', $vm->getEditBlockParams()['mod_id']);
        $this->assertSame('update', $vm->getUpdateParams()['mod_id']);
        $this->assertSame('media', $vm->getMediaParams()['mod_id']);
        $this->assertSame('themes', $vm->getThemesParams()['mod_id']);
    }
}
