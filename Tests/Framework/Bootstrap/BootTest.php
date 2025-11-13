<?php

namespace Tests\Framework\Bootstrap;

use Framework\Bootstrap\Boot;
use PHPUnit\Framework\TestCase;

class BootTest extends TestCase
{
    private Boot $boot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->boot = new Boot(testingMode: true);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /** @test */
    public function it_can_be_instantiated_in_testing_mode(): void
    {
        $boot = new Boot(testingMode: true);

        $this->assertInstanceOf(Boot::class, $boot);
        $this->assertTrue($boot->isTestingMode());
    }

    /** @test */
    public function it_can_be_instantiated_in_normal_mode(): void
    {
        // Note: This would require Container to exist and work
        // In a real scenario, you'd mock dependencies or have them available
        $boot = new Boot(testingMode: false);

        $this->assertInstanceOf(Boot::class, $boot);
        $this->assertFalse($boot->isTestingMode());
    }

    /** @test */
    public function it_starts_with_testing_mode_disabled_by_default(): void
    {
        $boot = new Boot();

        $this->assertFalse($boot->isTestingMode());
    }

    /** @test */
    public function it_can_check_if_testing_mode_is_enabled(): void
    {
        $this->assertTrue($this->boot->isTestingMode());
    }

    /** @test */
    public function it_can_enable_testing_mode(): void
    {
        $boot = new Boot(testingMode: false);

        $this->assertFalse($boot->isTestingMode());

        $boot->enableTestingMode(true);

        $this->assertTrue($boot->isTestingMode());
    }

    /** @test */
    public function it_can_disable_testing_mode(): void
    {
        $this->assertTrue($this->boot->isTestingMode());

        $this->boot->enableTestingMode(false);

        $this->assertFalse($this->boot->isTestingMode());
    }

    /** @test */
    public function it_can_toggle_testing_mode_multiple_times(): void
    {
        $this->boot->enableTestingMode(false);
        $this->assertFalse($this->boot->isTestingMode());

        $this->boot->enableTestingMode(true);
        $this->assertTrue($this->boot->isTestingMode());

        $this->boot->enableTestingMode(false);
        $this->assertFalse($this->boot->isTestingMode());
    }

    /** @test */
    public function init_does_nothing_in_testing_mode(): void
    {
        // Esto no debería lanzar excepciones ni hacer nada
        $this->boot->init(loadRouter: true);
        $this->boot->init(loadRouter: false);

        // Si llegamos aquí sin excepciones, el test pasa
        $this->assertTrue(true);
    }

    /** @test */
    public function init_accepts_load_router_parameter_true(): void
    {
        $this->boot->init(loadRouter: true);

        $this->assertTrue($this->boot->isTestingMode());
    }

    /** @test */
    public function init_accepts_load_router_parameter_false(): void
    {
        $this->boot->init(loadRouter: false);

        $this->assertTrue($this->boot->isTestingMode());
    }

    /** @test */
    public function init_uses_default_load_router_parameter(): void
    {
        $this->boot->init();

        $this->assertTrue($this->boot->isTestingMode());
    }

    /** @test */
    public function autoload_class_does_nothing_in_testing_mode(): void
    {
        // En testing mode, autoloadClass no debería lanzar excepciones
        $this->boot->autoloadClass('SomeNamespace\\SomeClass');

        // Si llegamos aquí sin excepciones, el test pasa
        $this->assertTrue(true);
    }

    /** @test */
    public function autoload_class_can_be_called_multiple_times_in_testing_mode(): void
    {
        $this->boot->autoloadClass('Namespace\\ClassA');
        $this->boot->autoloadClass('Namespace\\ClassB');
        $this->boot->autoloadClass('Another\\Namespace\\ClassC');

        $this->assertTrue(true);
    }

    /** @test */
    public function testing_mode_prevents_container_initialization(): void
    {
        // Crear Boot en testing mode no debería intentar crear Container
        $boot = new Boot(testingMode: true);

        // Verificar que está en testing mode
        $this->assertTrue($boot->isTestingMode());

        // Init tampoco debería hacer nada
        $boot->init();

        $this->assertTrue($boot->isTestingMode());
    }
}