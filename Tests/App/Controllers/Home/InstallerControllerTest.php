<?php

namespace Tests\App\Controllers\Home;

use App\Controllers\Home\InstallerController;
use App\DTO\InstallerDTO;
use App\Services\InstallerService;
use Framework\Attributes\InjectMocks;
use Framework\Attributes\SetupTestContainer;
use Framework\Core\View;
use Framework\Http\RequestHelper;
use Framework\Testing\TestCase;
use Framework\Utils\Flash;
use Framework\Utils\Redirect;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;

#[SetupTestContainer]
#[CoversClass(InstallerController::class)]
#[TestDox('Test del controlador InstallerController')]
class InstallerControllerTest extends TestCase
{
    #[InjectMocks]
    private ?InstallerController $controller = null;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    #[TestDox('Verifica que home renderice correctamente sin errores')]
    public function testHomeRendersWithoutErrors(): void
    {
        $flashMock = $this->getMock(Flash::class);
        $serviceMock = $this->getMock(InstallerService::class);
        $viewMock = $this->getMock(View::class);

        $flashMock->expects($this->once())
            ->method('has')
            ->with('errors')
            ->willReturn(false);

        $serviceMock->expects($this->once())
            ->method('prepareInstallerParams')
            ->with([])
            ->willReturn(['param' => 'value']);

        $viewMock->expects($this->once())
            ->method('render')
            ->with('installer/setup_wizard.html', ['param' => 'value'], false)
            ->willReturn('rendered content');

        $result = $this->controller->home();

        $this->assertSame('rendered content', $result);
    }

    #[Test]
    #[TestDox('Verifica que home renderice correctamente con errores en flash')]
    public function testHomeRendersWithErrors(): void
    {
        $flashMock = $this->getMock(Flash::class);
        $serviceMock = $this->getMock(InstallerService::class);
        $viewMock = $this->getMock(View::class);

        $flashMock->expects($this->once())
            ->method('has')
            ->with('errors')
            ->willReturn(true);

        $flashMock->expects($this->once())
            ->method('get')
            ->with('errors')
            ->willReturn(['error' => 'msg']);

        $serviceMock->expects($this->once())
            ->method('prepareInstallerParams')
            ->with(['errors' => ['error' => 'msg']])
            ->willReturn(['errors' => ['error' => 'msg'], 'param' => 'value']);

        $viewMock->expects($this->once())
            ->method('render')
            ->with('installer/setup_wizard.html', ['errors' => ['error' => 'msg'], 'param' => 'value'], false)
            ->willReturn('rendered content');

        $result = $this->controller->home();

        $this->assertSame('rendered content', $result);
    }

    #[Test]
    #[TestDox('Verifica que postInstall guarde configuracion si la validacion pasa')]
    public function testPostInstallSavesSettingsOnValidationSuccess(): void
    {
        $requestHelperMock = $this->getMock(RequestHelper::class);
        $serviceMock = $this->getMock(InstallerService::class);
        $redirectMock = $this->getMock(Redirect::class);

        // InstallerDTO is created inside the method, so we can't mock it directly passed to fromRequest unless we mock RequestHelper behavior or DTO behavior if possible.
        // However, the controller creates `new InstallerDTO()`. We can't mock `new` easily without refactoring or using overrides.
        // But we can mock the service receiving ANY InstallerDTO.

        $serviceMock->expects($this->once())
            ->method('validateInstaller')
            ->with($this->isInstanceOf(InstallerDTO::class))
            ->willReturn(true);

        $serviceMock->expects($this->once())
            ->method('mapAndSaveSettings')
            ->with($this->isInstanceOf(InstallerDTO::class));

        $redirectMock->expects($this->once())
            ->method('to')
            ->with('/installer/');

        $this->controller->postInstall();
    }

    #[Test]
    #[TestDox('Verifica que postInstall establezca errores flash si la validacion falla')]
    public function testPostInstallSetsFlashErrorsOnValidationFailure(): void
    {
        $requestHelperMock = $this->getMock(RequestHelper::class);
        $serviceMock = $this->getMock(InstallerService::class);
        $flashMock = $this->getMock(Flash::class);
        $redirectMock = $this->getMock(Redirect::class);

        $serviceMock->expects($this->once())
            ->method('validateInstaller')
            ->willReturn(false);

        $serviceMock->expects($this->once())
            ->method('getValidationErrors')
            ->willReturn(['field' => 'error']);

        $flashMock->expects($this->once())
            ->method('set')
            ->with('errors', ['field' => 'error']);

        $redirectMock->expects($this->once())
            ->method('to')
            ->with('/installer/');

        $this->controller->postInstall();
    }

    #[Test]
    #[TestDox('Verifica que getProgressBar redirija si no hay instalacion en progreso')]
    public function testGetProgressBarRedirectsIfNoInstallInProgress(): void
    {
        $flashMock = $this->getMock(Flash::class);
        $redirectMock = $this->getMock(Redirect::class);

        $flashMock->expects($this->once())
            ->method('has')
            ->with('install_in_progress')
            ->willReturn(false);

        $redirectMock->expects($this->once())
            ->method('to')
            ->with('/installer/');

        $this->controller->getProgressBar();
    }

    #[Test]
    #[TestDox('Verifica que getProgressBar renderice correctamente si hay instalacion en progreso')]
    public function testGetProgressBarRendersIfInstallInProgress(): void
    {
        $flashMock = $this->getMock(Flash::class);
        $serviceMock = $this->getMock(InstallerService::class);
        $viewMock = $this->getMock(View::class);

        $flashMock->expects($this->once())
            ->method('has')
            ->with('install_in_progress')
            ->willReturn(true);

        $serviceMock->expects($this->once())
            ->method('markAsInstalled');

        $serviceMock->expects($this->once())
            ->method('prepareProgressBarParams')
            ->willReturn(['progress' => 100]);

        $flashMock->expects($this->once())
            ->method('delete')
            ->with('install_in_progress');

        $viewMock->expects($this->once())
            ->method('render')
            ->with('installer/progressBar.html', ['progress' => 100], false)
            ->willReturn('progress html');

        $result = $this->controller->getProgressBar();

        $this->assertSame('progress html', $result);
    }

    #[Test]
    #[TestDox('Verifica que postProgressBar inicie instalacion y redirija')]
    public function testPostProgressBarStartsInstallAndRedirects(): void
    {
        $flashMock = $this->getMock(Flash::class);
        $serviceMock = $this->getMock(InstallerService::class);
        $redirectMock = $this->getMock(Redirect::class);

        $flashMock->expects($this->once())
            ->method('set')
            ->with('install_in_progress', true);

        $serviceMock->expects($this->once())
            ->method('executeInstallation');

        $redirectMock->expects($this->once())
            ->method('to')
            ->with('/installer/progressBar');

        $this->controller->postProgressBar();
    }
}
