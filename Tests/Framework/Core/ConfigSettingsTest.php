<?php

namespace Tests\Framework\Core;

use Framework\Core\ConfigSettings;
use Framework\Core\JSONHandler;
use Framework\Config\SetupConfig;
use Framework\Exceptions\ConfigException;
use Framework\Testing\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ConfigSettings::class)]
#[TestDox('Test de la clase ConfigSettings')]
class ConfigSettingsTest extends TestCase
{
    private string $tmpFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpFile = tempnam(sys_get_temp_dir(), 'config_');

        $data = [
            'config' => [
                'database' => [
                    'dbhost' => 'localhost',
                    'dbuser' => 'root',
                    'dbpass' => '1234',
                    'dbname' => 'balero'
                ],
                'admin' => [
                    'username' => 'admin',
                    'passwd' => 'secret',
                    'email' => 'admin@example.com',
                    'firstname' => 'John',
                    'lastname' => 'Doe'
                ],
                'site' => [
                    'language' => 'es',
                    'title' => 'Balero CMS',
                    'description' => 'CMS description',
                    'url' => 'https://balero.dev',
                    'keywords' => 'cms, balero',
                    'basepath' => '/',
                    'theme' => 'default',
                    'footer' => 'Footer text',
                    'multilang' => 'yes',
                    'editor' => 'tinymce'
                ],
                'system' => [
                    'installed' => 'yes'
                ]
            ]
        ];

        file_put_contents($this->tmpFile, json_encode($data, JSON_PRETTY_PRINT));
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tmpFile)) {
            unlink($this->tmpFile);
        }
        parent::tearDown();
    }

    #[Test]
    #[TestDox('Carga correctamente los valores desde el archivo JSON')]
    public function testLoadsJsonFileContent(): void
    {
        $setup = new SetupConfig($this->tmpFile);
        $config = new ConfigSettings($setup);
        $config->getHandler();

        $this->assertSame('localhost', $config->dbhost);
        $this->assertSame('admin', $config->username);
        $this->assertSame('Balero CMS', $config->title);
        $this->assertSame('secret', $config->pass);
        $this->assertSame('default', $config->theme);
    }

    #[Test]
    #[TestDox('Actualiza un valor correctamente con __set')]
    public function testSetUpdatesValue(): void
    {
        $setup = new SetupConfig($this->tmpFile);
        $config = new ConfigSettings($setup);
        $config->getHandler();

        $config->email = 'new@example.com';
        $this->assertSame('new@example.com', $config->email);

        $handler = new JSONHandler($this->tmpFile);
        $this->assertSame('new@example.com', $handler->get('/config/admin/email'));
    }

    #[Test]
    #[TestDox('Lanza excepción al intentar establecer una propiedad inexistente')]
    public function testThrowsOnInvalidProperty(): void
    {
        $setup = new SetupConfig($this->tmpFile);
        $config = new ConfigSettings($setup);
        $config->getHandler();

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('Property not exist: nonexistent');
        $config->nonexistent = 'value';
    }

    #[Test]
    #[TestDox('Retorna null al acceder a una propiedad no definida con __get')]
    public function testReturnsNullForUndefinedProperty(): void
    {
        $setup = new SetupConfig($this->tmpFile);
        $config = new ConfigSettings($setup);
        $config->getHandler();

        $this->assertNull($config->nonexistent);
    }

    #[Test]
    #[TestDox('Genera correctamente el basepath para root')]
    public function testGetFullBasepathGeneratesValidPath(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $setup = new SetupConfig($this->tmpFile);
        $config = new ConfigSettings($setup);
        $config->getHandler();

        $path = $config->getFullBasepath();

        $this->assertSame('/', $path);
        $this->assertStringEndsWith('/', $path);
    }

    #[Test]
    #[TestDox('Genera basepath correcto en subdirectorio')]
    public function testGetFullBasepathWithSubdirectory(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/test/index.php';

        $setup = new SetupConfig($this->tmpFile);
        $config = new ConfigSettings($setup);
        $config->getHandler();

        $path = $config->getFullBasepath();

        $this->assertSame('/test/', $path);
        $this->assertStringEndsWith('/', $path);
    }

    #[Test]
    #[TestDox('Genera basepath con subdirectorio más profundo')]
    public function testGetFullBasepathWithDeepSubdirectory(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/myapp/public/index.php';

        $setup = new SetupConfig($this->tmpFile);
        $config = new ConfigSettings($setup);
        $config->getHandler();

        $path = $config->getFullBasepath();

        $this->assertSame('/myapp/public/', $path);
        $this->assertStringEndsWith('/', $path);
    }

    #[Test]
    #[TestDox('Lanza excepción si el archivo de configuración no existe')]
    public function testThrowsIfFileNotFound(): void
    {
        $setup = new SetupConfig('/path/to/nonexistent.json');
        $config = new ConfigSettings($setup);
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('File not found: /path/to/nonexistent.json');
        $config->getHandler();
    }

    #[Test]
    #[TestDox('El handler se inicializa de forma lazy')]
    public function testHandlerIsLazyLoaded(): void
    {
        $setup = new SetupConfig($this->tmpFile);
        $config = new ConfigSettings($setup);

        $handler = $config->getHandler();
        $this->assertInstanceOf(JSONHandler::class, $handler);
        $this->assertSame($handler, $config->getHandler());
    }

    #[Test]
    #[TestDox('Retorna correctamente el path del archivo de configuración')]
    public function testGetConfigPath(): void
    {
        $setup = new SetupConfig($this->tmpFile);
        $config = new ConfigSettings($setup);
        $this->assertSame($this->tmpFile, $config->getConfigPath());
    }

    #[Test]
    #[TestDox('Permite cambiar el path del archivo de configuración')]
    public function testSetConfigPath(): void
    {
        $setup = new SetupConfig($this->tmpFile);
        $config = new ConfigSettings($setup);
        $oldPath = $config->getConfigPath();

        $newFile = tempnam(sys_get_temp_dir(), 'config_new_');
        copy($this->tmpFile, $newFile);

        $config->setConfigPath($newFile);
        $this->assertSame($newFile, $config->getConfigPath());
        $this->assertNotSame($oldPath, $config->getConfigPath());

        unlink($newFile);
    }

    #[Test]
    #[TestDox('Resetea el handler al cambiar el path de configuración')]
    public function testSetConfigPathResetsHandler(): void
    {
        $setup = new SetupConfig($this->tmpFile);
        $config = new ConfigSettings($setup);
        $handler1 = $config->getHandler();

        $newFile = tempnam(sys_get_temp_dir(), 'config_new_');
        copy($this->tmpFile, $newFile);

        $config->setConfigPath($newFile);
        $handler2 = $config->getHandler();

        $this->assertNotSame($handler1, $handler2);

        unlink($newFile);
    }

    #[Test]
    #[TestDox('Retorna todos los datos cargados con getData()')]
    public function testGetDataReturnsAllLoadedData(): void
    {
        $setup = new SetupConfig($this->tmpFile);
        $config = new ConfigSettings($setup);
        $config->getHandler();

        $data = $config->getData();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('dbhost', $data);
        $this->assertArrayHasKey('username', $data);
        $this->assertArrayHasKey('title', $data);
        $this->assertSame('localhost', $data['dbhost']);
        $this->assertSame('admin', $data['username']);
    }

    #[Test]
    #[TestDox('LoadSettings carga todos los campos definidos')]
    public function testLoadSettingsLoadsAllFields(): void
    {
        $setup = new SetupConfig($this->tmpFile);
        $config = new ConfigSettings($setup);
        $config->getHandler();

        $data = $config->getData();

        $expectedFields = [
            'dbhost', 'dbuser', 'dbpass', 'dbname',
            'username', 'pass', 'email', 'firstname', 'lastname',
            'installed',
            'language', 'title', 'description', 'url', 'keywords',
            'basepath', 'theme', 'footer', 'multilang', 'editor'
        ];

        foreach ($expectedFields as $field) {
            $this->assertArrayHasKey($field, $data, "Campo '$field' no está cargado");
        }
    }
}
