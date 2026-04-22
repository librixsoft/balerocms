<?php

declare(strict_types=1);

namespace Tests\App\Services;

use App\Models\Admin\AdminPagesModel;
use App\Models\Admin\AdminBlocksModel;
use App\Models\Admin\AdminMediaModel;
use App\Services\Admin\AdminSystemService;
use App\Services\Admin\AdminSettingsService;
use App\Services\Admin\AdminMediaService;
use App\Services\Admin\AdminThemesService;
use App\Services\Admin\AdminUpdateService;
use App\Services\UpdateService;
use App\Services\UploaderService;
use App\Views\AdminViewModel;
use App\Exceptions\Admin\ThemeException;
use Framework\Core\ConfigSettings;
use PHPUnit\Framework\TestCase;

final class AdminSystemServiceTest extends TestCase
{
    private array $tempDirs = [];
    private array $tempPaths = [];

    protected function tearDown(): void
    {
        foreach ($this->tempPaths as $path) {
            $this->removePath($path);
        }
        foreach ($this->tempDirs as $dir) {
            $this->removeDir($dir);
        }
        $this->tempPaths = [];
        $this->tempDirs = [];
        parent::tearDown();
    }

    private function makeTempDir(): string
    {
        $base = sys_get_temp_dir() . '/balerocms_adminsystemservice_' . bin2hex(random_bytes(6));
        if (!is_dir($base)) {
            mkdir($base, 0777, true);
        }
        $this->tempDirs[] = $base;
        return $base;
    }

    private function makeZip(string $zipPath, array $files): void
    {
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE);
        foreach ($files as $path => $content) {
            $zip->addFromString($path, $content);
        }
        $zip->close();
    }

    private function getBasePath(): string
    {
        if (defined('BASE_PATH')) {
            return BASE_PATH;
        }
        $base = $this->makeTempDir();
        define('BASE_PATH', $base);
        return $base;
    }

    private function removePath(string $path): void
    {
        if (is_file($path)) {
            @unlink($path);
            return;
        }
        if (is_dir($path)) {
            $this->removeDir($path);
        }
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                @rmdir($file->getPathname());
            } else {
                @unlink($file->getPathname());
            }
        }
        @rmdir($dir);
    }

    private function makeService(
        ?AdminPagesModel $pagesModel = null,
        ?AdminBlocksModel $blocksModel = null,
        ?AdminMediaModel $mediaModel = null,
        ?AdminViewModel $vm = null,
        ?UpdateService $update = null,
        ?UploaderService $uploader = null,
        ?ConfigSettings $configSettings = null,
        ?\Framework\Utils\Validator $validator = null,
        ?\App\Mapper\AdminSettingsMapper $mapper = null,
    ): AdminSystemService {
        $svc = new AdminSystemService();

        $settingsSvc = new AdminSettingsService();
        $mediaSvc = new AdminMediaService();
        $themesSvc = new AdminThemesService();
        $updateSvc = new AdminUpdateService();

        $configSettings = $configSettings ?? $this->createMock(ConfigSettings::class);
        $vm = $vm ?? $this->createMock(AdminViewModel::class);
        $validator = $validator ?? $this->createMock(\Framework\Utils\Validator::class);
        $uploader = $uploader ?? $this->createMock(UploaderService::class);
        $update = $update ?? $this->createMock(UpdateService::class);
        $mapper = $mapper ?? $this->createMock(\App\Mapper\AdminSettingsMapper::class);

        $dbModel = $this->createMock(\Framework\Core\Model::class);
        $utils = $this->createMock(\Framework\Utils\Utils::class);
        $pagesModel = $pagesModel ?? new AdminPagesModel($dbModel, $utils);
        $blocksModel = $blocksModel ?? new AdminBlocksModel($dbModel);
        $mediaModel = $mediaModel ?? new AdminMediaModel($dbModel);

        $this->injectService($settingsSvc, [
            'pagesModel' => $pagesModel,
            'blocksModel' => $blocksModel,
            'mediaModel' => $mediaModel,
            'viewModel' => $vm,
            'validator' => $validator,
            'adminSettingsMapper' => $mapper,
            'configSettings' => $configSettings,
        ]);

        $this->injectService($mediaSvc, [
            'model' => $mediaModel,
            'pagesModel' => $pagesModel,
            'blocksModel' => $blocksModel,
            'viewModel' => $vm,
            'configSettings' => $configSettings,
            'uploaderService' => $uploader,
        ]);

        $this->injectService($themesSvc, [
            'pagesModel' => $pagesModel,
            'blocksModel' => $blocksModel,
            'mediaModel' => $mediaModel,
            'viewModel' => $vm,
            'configSettings' => $configSettings,
        ]);

        $this->injectService($updateSvc, [
            'pagesModel' => $pagesModel,
            'blocksModel' => $blocksModel,
            'mediaModel' => $mediaModel,
            'viewModel' => $vm,
            'updateService' => $update,
        ]);

        $this->injectService($svc, [
            'settingsService' => $settingsSvc,
            'mediaService' => $mediaSvc,
            'themesService' => $themesSvc,
            'updateService' => $updateSvc,
        ]);

        return $svc;
    }

    private function injectService($service, array $deps): void
    {
        $r = new \ReflectionClass($service);
        foreach ($deps as $prop => $val) {
            if ($r->hasProperty($prop)) {
                $p = $r->getProperty($prop);
                $p->setAccessible(true);
                $p->setValue($service, $val);
            }
        }
    }

    public function testBasicViewParams(): void
    {
        $pagesModel = $this->createMock(AdminPagesModel::class);
        $pagesModel->method('getPagesCount')->willReturn(10);

        $blocksModel = $this->createMock(AdminBlocksModel::class);
        $blocksModel->method('getBlocksCount')->willReturn(20);

        $mediaModel = $this->createMock(AdminMediaModel::class);
        $mediaModel->method('getAllMedia')->willReturn([]);

        $vm = $this->createMock(AdminViewModel::class);
        $vm->method('getSettingsParams')->willReturnArgument(0);
        $vm->method('getUpdateParams')->willReturnArgument(0);
        $vm->method('getMediaParams')->willReturnArgument(0);
        $vm->method('getThemesParams')->willReturnArgument(0);

        $update = $this->createMock(UpdateService::class);
        $update->method('isUpdateAvailable')->willReturn(['update_available' => false]);

        $uploader = $this->createMock(UploaderService::class);
        $uploader->method('getAllMediaJson')->willReturn([['name' => 'x.jpg']]);

        $svc = $this->makeService(
            pagesModel: $pagesModel,
            blocksModel: $blocksModel,
            mediaModel: $mediaModel,
            vm: $vm,
            update: $update,
            uploader: $uploader
        );

        $this->assertArrayHasKey('media_count', $svc->getSettingsViewParams());
        $this->assertArrayHasKey('update_available', $svc->getUpdateViewParams());
        $this->assertArrayHasKey('media_items', $svc->getMediaViewParams());
        $this->assertArrayHasKey('media_count', $svc->getThemesViewParams());
    }

    public function testUpdateDelegation(): void
    {
        $update = $this->createMock(UpdateService::class);
        $update->expects($this->once())->method('selfUpdate')->willReturn(['success' => true]);
        $update->expects($this->once())->method('performUpdate')->willReturn(['success' => true]);

        $svc = $this->makeService(update: $update);

        $this->assertTrue($svc->selfUpdateService()['success']);
        $this->assertTrue($svc->performUpdate()['success']);
    }

    public function testValidationAndMappingDelegation(): void
    {
        $validator = $this->createMock(\Framework\Utils\Validator::class);
        $validator->expects($this->once())->method('validate');
        $validator->method('fails')->willReturn(false);
        $validator->expects($this->once())->method('errors')->willReturn(['a' => 'b']);

        $mapper = $this->createMock(\App\Mapper\AdminSettingsMapper::class);
        $mapper->expects($this->once())->method('mapAndSaveSettings');

        $settings = $this->createMock(\App\DTO\SettingsDTO::class);

        $svc = $this->makeService(configSettings: $this->createMock(ConfigSettings::class), validator: $validator, mapper: $mapper);

        $this->assertTrue($svc->validateSettings($settings));
        $svc->mapAndSaveSettings($settings);
        $this->assertSame(['a' => 'b'], $svc->getValidationErrors());
    }

    public function testUploadThemeZipHappyPathAndReplacesExisting(): void
    {
        $base = $this->getBasePath();
        $docRoot = $this->makeTempDir() . '/public';
        $_SERVER['DOCUMENT_ROOT'] = $docRoot;
        if (!is_dir($_SERVER['DOCUMENT_ROOT'])) {
            mkdir($_SERVER['DOCUMENT_ROOT'], 0777, true);
        }

        $theme = 'My-Theme-' . bin2hex(random_bytes(3));
        $resourcesDir = $base . '/resources/views/themes/' . $theme;
        $publicDir = $_SERVER['DOCUMENT_ROOT'] . '/assets/themes/' . $theme;
        mkdir($resourcesDir, 0777, true);
        mkdir($publicDir, 0777, true);
        $this->tempPaths[] = $resourcesDir;
        $this->tempPaths[] = $publicDir;
        file_put_contents($resourcesDir . '/old.txt', 'old');
        file_put_contents($publicDir . '/old.css', 'old');

        $zipDir = $this->makeTempDir();
        $zipPath = $zipDir . '/theme.zip';
        $this->makeZip($zipPath, [
            'theme/main.html' => '<html></html>',
            'theme/assets/style.css' => 'body{}',
        ]);

        $svc = $this->makeService();
        $svc->uploadThemeZip([
            'tmp_name' => $zipPath,
            'name' => $theme . '!.zip',
        ]);

        $this->assertFileExists($resourcesDir . '/main.html');
        $this->assertFileExists($publicDir . '/assets/style.css');
        $this->assertFileDoesNotExist($resourcesDir . '/old.txt');
        $this->assertFileDoesNotExist($publicDir . '/old.css');
    }

    public function testUploadThemeZipInvalidZip(): void
    {
        $zipDir = $this->makeTempDir();
        $zipPath = $zipDir . '/bad.zip';
        file_put_contents($zipPath, 'not a zip');

        $svc = $this->makeService();
        $this->expectException(ThemeException::class);
        $this->expectExceptionMessage('Invalid ZIP file.');
        $svc->uploadThemeZip([
            'tmp_name' => $zipPath,
            'name' => 'Bad.zip',
        ]);
    }

    public function testUploadThemeZipInvalidThemeName(): void
    {
        $zipDir = $this->makeTempDir();
        $zipPath = $zipDir . '/theme.zip';
        $this->makeZip($zipPath, ['main.html' => '<html></html>']);

        $svc = $this->makeService();
        $this->expectException(ThemeException::class);
        $this->expectExceptionMessage('Invalid theme name.');
        $svc->uploadThemeZip([
            'tmp_name' => $zipPath,
            'name' => '!!!.zip',
        ]);
    }

    public function testActivateThemeMissingDirThrows(): void
    {
        $configSettings = new class extends ConfigSettings {
            public string $theme = '';
            public function __construct() {}
            public function __get(string $name) { return $this->$name ?? null; }
            public function __set(string $name, string $value) { $this->$name = $value; }
        };

        $svc = $this->makeService(configSettings: $configSettings);
        $this->expectException(ThemeException::class);
        $this->expectExceptionMessage('Theme does not exist.');
        $svc->activateTheme('missing');
    }

    public function testActivateThemeSetsConfig(): void
    {
        $base = $this->getBasePath();
        $theme = 'theme-' . bin2hex(random_bytes(3));
        $resourcesDir = $base . '/resources/views/themes/' . $theme;
        mkdir($resourcesDir, 0777, true);
        $this->tempPaths[] = $resourcesDir;

        $configSettings = new class extends ConfigSettings {
            public string $theme = '';
            public function __construct() {}
            public function __get(string $name) { return $this->$name ?? null; }
            public function __set(string $name, string $value) { $this->$name = $value; }
        };

        $svc = $this->makeService(configSettings: $configSettings);
        $svc->activateTheme($theme);
        $this->assertSame($theme, $configSettings->theme);
    }

    public function testDeleteThemeValidationActiveThemeAndRemoval(): void
    {
        $configSettings = new class extends ConfigSettings {
            public string $theme = '';
            public function __construct() {}
            public function __get(string $name) { return $this->$name ?? null; }
            public function __set(string $name, string $value) { $this->$name = $value; }
        };

        $svc = $this->makeService(configSettings: $configSettings);

        $this->expectException(ThemeException::class);
        $this->expectExceptionMessage('Invalid theme name.');
        $svc->deleteTheme('!!!');
    }

    public function testDeleteThemeActiveThemeThrows(): void
    {
        $configSettings = new class extends ConfigSettings {
            public string $theme = '';
            public function __construct() {}
            public function __get(string $name) { return $this->$name ?? null; }
            public function __set(string $name, string $value) { $this->$name = $value; }
        };
        $configSettings->theme = 'active';

        $svc = $this->makeService(configSettings: $configSettings);
        $this->expectException(ThemeException::class);
        $this->expectExceptionMessage('Cannot delete the active theme.');
        $svc->deleteTheme('active');
    }

    public function testDeleteThemeRemovesDirectories(): void
    {
        $base = $this->getBasePath();
        $docRoot = $this->makeTempDir() . '/public';
        $_SERVER['DOCUMENT_ROOT'] = $docRoot;
        if (!is_dir($_SERVER['DOCUMENT_ROOT'])) {
            mkdir($_SERVER['DOCUMENT_ROOT'], 0777, true);
        }

        $configSettings = new class extends ConfigSettings {
            public string $theme = '';
            public function __construct() {}
            public function __get(string $name) { return $this->$name ?? null; }
            public function __set(string $name, string $value) { $this->$name = $value; }
        };
        $configSettings->theme = 'active';

        $theme = 'oldtheme-' . bin2hex(random_bytes(3));
        $resourcesDir = $base . '/resources/views/themes/' . $theme;
        $publicDir = $_SERVER['DOCUMENT_ROOT'] . '/assets/themes/' . $theme;
        mkdir($resourcesDir, 0777, true);
        mkdir($publicDir, 0777, true);
        $this->tempPaths[] = $resourcesDir;
        $this->tempPaths[] = $publicDir;

        $svc = $this->makeService(configSettings: $configSettings);
        $svc->deleteTheme($theme);

        $this->assertDirectoryDoesNotExist($resourcesDir);
        $this->assertDirectoryDoesNotExist($publicDir);
    }
}
