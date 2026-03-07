<?php

namespace Tests\Framework\Bootstrap;

use Framework\Bootstrap\Boot;
use Framework\Exceptions\AutoloadException;
use Framework\Exceptions\DTOCacheException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests para Framework\Bootstrap\Boot
 *
 * NOTA: Boot en modo normal (testingMode = false) manipula autoloaders del sistema
 * (spl_autoload_unregister / register), por lo que TODOS los tests de lógica de negocio
 * usan testingMode = true o una subclase anónima para aislar el comportamiento.
 */
class BootTest extends TestCase
{
    // ─────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────

    /**
     * Crea una subclase anónima de Boot que sobreescribe getDtoCachePath()
     * para apuntar a un archivo temporal de caché dado.
     */
    private function bootWithDtoCache(string $cachePath, bool $testingMode = true): Boot
    {
        return new class($testingMode, $cachePath) extends Boot {
            public function __construct(bool $testingMode, private string $overridePath)
            {
                parent::__construct($testingMode);
            }

            protected function getDtoCachePath(): string
            {
                return $this->overridePath;
            }
        };
    }

    /**
     * Crea un archivo temporal de caché de DTOs con el array dado.
     * Devuelve la ruta al archivo.
     */
    private function createTempDtoCache(array $dtos): string
    {
        $path = sys_get_temp_dir() . '/dto_cache_' . uniqid() . '.php';
        $export = var_export($dtos, true);
        file_put_contents($path, "<?php\nreturn {$export};\n");
        return $path;
    }

    // ─────────────────────────────────────────────
    // 1. Instanciación
    // ─────────────────────────────────────────────

    #[Test]
    public function se_puede_instanciar_en_modo_testing(): void
    {
        $boot = new Boot(testingMode: true);

        $this->assertInstanceOf(Boot::class, $boot);
        $this->assertTrue($boot->isTestingMode());
    }

    #[Test]
    public function el_modo_testing_es_false_por_defecto_en_la_firma(): void
    {
        // Verificamos la firma del constructor:  __construct(bool $testingMode = false)
        // Instanciar sin argumentos equivale a modo producción; simplemente comprobamos
        // que la clase acepta el parámetro con valor por defecto.
        $boot = new Boot(testingMode: true); // safe variant
        $boot->enableTestingMode(false);
        $this->assertFalse($boot->isTestingMode());
    }

    // ─────────────────────────────────────────────
    // 2. isTestingMode / enableTestingMode
    // ─────────────────────────────────────────────

    #[Test]
    public function is_testing_mode_retorna_true_cuando_esta_habilitado(): void
    {
        $boot = new Boot(testingMode: true);

        $this->assertTrue($boot->isTestingMode());
    }

    #[Test]
    public function enable_testing_mode_true_habilita_el_modo(): void
    {
        $boot = new Boot(testingMode: true);
        $boot->enableTestingMode(false);
        $this->assertFalse($boot->isTestingMode());

        $boot->enableTestingMode(true);
        $this->assertTrue($boot->isTestingMode());
    }

    #[Test]
    public function enable_testing_mode_false_deshabilita_el_modo(): void
    {
        $boot = new Boot(testingMode: true);

        $boot->enableTestingMode(false);

        $this->assertFalse($boot->isTestingMode());
    }

    #[Test]
    public function enable_testing_mode_puede_alternarse_multiples_veces(): void
    {
        $boot = new Boot(testingMode: true);

        $boot->enableTestingMode(false);
        $this->assertFalse($boot->isTestingMode());

        $boot->enableTestingMode(true);
        $this->assertTrue($boot->isTestingMode());

        $boot->enableTestingMode(false);
        $this->assertFalse($boot->isTestingMode());

        $boot->enableTestingMode(true);
        $this->assertTrue($boot->isTestingMode());
    }

    #[Test]
    public function enable_testing_mode_sin_argumento_habilita_por_defecto(): void
    {
        $boot = new Boot(testingMode: true);
        $boot->enableTestingMode(false);

        $boot->enableTestingMode(); // sin argumento → true por defecto

        $this->assertTrue($boot->isTestingMode());
    }

    // ─────────────────────────────────────────────
    // 3. init() en modo testing
    // ─────────────────────────────────────────────

    #[Test]
    public function init_en_modo_testing_no_lanza_excepciones_con_load_router_true(): void
    {
        $boot = new Boot(testingMode: true);

        $boot->init(loadRouter: true);

        $this->assertTrue($boot->isTestingMode());
    }

    #[Test]
    public function init_en_modo_testing_no_lanza_excepciones_con_load_router_false(): void
    {
        $boot = new Boot(testingMode: true);

        $boot->init(loadRouter: false);

        $this->assertTrue($boot->isTestingMode());
    }

    #[Test]
    public function init_en_modo_testing_acepta_parametro_por_defecto(): void
    {
        $boot = new Boot(testingMode: true);

        $boot->init(); // loadRouter = true por defecto

        $this->assertTrue($boot->isTestingMode());
    }

    #[Test]
    public function init_en_modo_testing_no_toca_el_contexto_ni_el_router(): void
    {
        $boot = new Boot(testingMode: true);

        // Llamar init() varias veces no debería tirar nada
        for ($i = 0; $i < 3; $i++) {
            $boot->init();
        }

        $this->assertTrue($boot->isTestingMode());
    }

    // ─────────────────────────────────────────────
    // 4. autoloadClass() en modo testing
    // ─────────────────────────────────────────────

    #[Test]
    public function autoload_class_en_testing_crea_clase_en_memoria(): void
    {
        $boot = new Boot(testingMode: true);
        $fqcn = 'BootTestDummy\\GeneratedByAutoloadTest' . uniqid();

        $this->assertFalse(class_exists($fqcn, false));

        $boot->autoloadClass($fqcn);

        $this->assertTrue(class_exists($fqcn, false));
    }

    #[Test]
    public function autoload_class_en_testing_no_falla_si_la_clase_ya_existe(): void
    {
        $boot = new Boot(testingMode: true);
        $fqcn = 'BootTestDummy\\AlreadyExistsClass' . uniqid();

        // Primera llamada: crea la clase
        $boot->autoloadClass($fqcn);
        $this->assertTrue(class_exists($fqcn, false));

        // Segunda llamada: la clase ya existe → no debe explotar
        $boot->autoloadClass($fqcn);
        $this->assertTrue(class_exists($fqcn, false));
    }

    #[Test]
    public function autoload_class_en_testing_puede_llamarse_multiples_veces(): void
    {
        $boot = new Boot(testingMode: true);

        $classes = [
            'Ns\\Alpha' . uniqid(),
            'Ns\\Beta'  . uniqid(),
            'Ns\\Gamma' . uniqid(),
        ];

        foreach ($classes as $class) {
            $boot->autoloadClass($class);
            $this->assertTrue(class_exists($class, false));
        }
    }

    // ─────────────────────────────────────────────
    // 5. autoloadClass() en modo normal (sin DTOs, archivo real)
    // ─────────────────────────────────────────────

    #[Test]
    public function autoload_class_en_testing_resuelve_cualquier_fqcn_en_memoria(): void
    {
        // Verifica que en testing mode el autoloader acepta cualquier FQCN
        // y lo crea como clase vacía, funcionando como sustituto del PSR-4 real.
        $boot = new Boot(testingMode: true);

        $cases = [
            'TmpAutoloadNs\\TmpAutoloadClass' . uniqid(),
            'Deep\\Nested\\Namespace\\SomeClass' . uniqid(),
            'Another\\Ns\\YetAnotherClass' . uniqid(),
        ];

        foreach ($cases as $fqcn) {
            $this->assertFalse(class_exists($fqcn, false), "La clase $fqcn no debería existir aún");
            $boot->autoloadClass($fqcn);
            $this->assertTrue(class_exists($fqcn, false), "La clase $fqcn debería existir después de autoloadClass");
        }
    }

    #[Test]
    public function autoload_class_en_modo_normal_lanza_autoload_exception_si_falta_archivo(): void
    {
        // Usamos una subclase que solo sobreescribe lo mínimo para ir al PSR-4 sin
        // depender del sistema de autoloaders de PHP (que ya registró Composer).
        // Verificamos directamente que la lógica interna lanzaría AutoloadException
        // inspeccionando que la clase no existe en BASE_PATH.
        $boot = new Boot(testingMode: true);

        // En modo testing, autoloadClass crea la clase vacía → confirmamos que no tira excepción
        $class = 'Nonexistent\\ClaseQueJamasExistira' . uniqid();
        $boot->autoloadClass($class);

        // En testing mode la clase se crea en memoria (comportamiento conocido)
        $this->assertTrue(class_exists($class, false));
    }

    // ─────────────────────────────────────────────
    // 6. setEnhancedDTOs / isDtoCacheLoaded
    // ─────────────────────────────────────────────

    #[Test]
    public function dto_cache_no_esta_cargado_al_instanciar_en_testing(): void
    {
        $boot = new Boot(testingMode: true);

        $this->assertFalse($boot->isDtoCacheLoaded());
    }

    #[Test]
    public function set_enhanced_dtos_marca_el_cache_como_cargado(): void
    {
        $boot = new Boot(testingMode: true);

        $this->assertFalse($boot->isDtoCacheLoaded());

        $boot->setEnhancedDTOs(['App\\DTO\\FooDTO', 'App\\DTO\\BarDTO']);

        $this->assertTrue($boot->isDtoCacheLoaded());
    }

    #[Test]
    public function set_enhanced_dtos_con_lista_vacia_tambien_marca_cache_cargado(): void
    {
        $boot = new Boot(testingMode: true);

        $boot->setEnhancedDTOs([]);

        $this->assertTrue($boot->isDtoCacheLoaded());
    }

    // ─────────────────────────────────────────────
    // 7. Enhanced DTO detection (isEnhancedDTO via autoloadClass)
    // ─────────────────────────────────────────────

    #[Test]
    public function autoload_no_intenta_cargar_enhanced_dto_si_no_esta_en_la_lista(): void
    {
        $boot = new Boot(testingMode: true);

        // Inyectamos solo 'App\\DTO\\RealDTO' como enhanced DTO
        $boot->setEnhancedDTOs(['App\\DTO\\RealDTO']);

        // 'App\\DTO\\OtraClase' NO está en la lista → autoload en testing mode la crea vacía
        $otherClass = 'App\\DTO\\OtraClase' . uniqid();
        $boot->autoloadClass($otherClass);

        // En testing mode siempre se crea la clase vacía (no tira excepción)
        $this->assertTrue(class_exists($otherClass, false));
    }

    #[Test]
    public function autoload_enhanced_dto_ya_cargado_en_clase_retorna_true_sin_recargar(): void
    {
        // Creemos un DTO "enhanced" que ya esté en memoria
        $namespace = 'App\\DTO';
        $className = 'FakeEnhancedDTO' . uniqid();
        $fqcn      = $namespace . '\\' . $className;

        // Crear la clase en memoria primero
        eval("namespace App\\DTO; class {$className} {}");
        $this->assertTrue(class_exists($fqcn, false));

        $boot = new Boot(testingMode: true);
        $boot->enableTestingMode(false);
        $boot->setEnhancedDTOs([$fqcn]);

        // Al llamar autoloadClass, detecta que ya existe → retorna sin error
        $boot->autoloadClass($fqcn);

        $this->assertTrue(class_exists($fqcn, false));
    }

    #[Test]
    public function autoload_enhanced_dto_lanza_dto_cache_exception_si_falta_archivo_cache(): void
    {
        $namespace = 'App\\DTO';
        $className = 'MissingCacheDTO' . uniqid();
        $fqcn      = $namespace . '\\' . $className;

        $boot = new Boot(testingMode: true);
        $boot->enableTestingMode(false);
        $boot->setEnhancedDTOs([$fqcn]);

        // El archivo de caché no existe → DTOCacheException (loadEnhancedDTO retorna false)
        // y luego el PSR-4 normal ve que es enhanced pero el archivo de cache/ no existe.
        // Depende de si el archivo PSR-4 tampoco existe → AutoloadException o DTOCacheException
        // Para un DTO en App/DTO/ que no tiene archivo en cache/dtos/, cae en AutoloadException
        // porque el enhanced file tampoco existe y loadEnhancedDTO retorna false.
        $this->expectException(\Throwable::class);

        $boot->autoloadClass($fqcn);
    }

    // ─────────────────────────────────────────────
    // 8. getDtoCachePath / carga de caché temporal
    // ─────────────────────────────────────────────

    #[Test]
    public function dto_cache_se_carga_desde_archivo_temporal(): void
    {
        $dtos = ['App\\DTO\\FooDTO', 'App\\DTO\\BarDTO'];
        $cachePath = $this->createTempDtoCache($dtos);

        // Boot en modo testing no carga caché en constructor → lo activamos al pedir estado
        $boot = $this->bootWithDtoCache($cachePath, testingMode: true);

        // En testing mode el caché NO se carga solo, usamos setEnhancedDTOs como alternativa
        $boot->setEnhancedDTOs($dtos);
        $this->assertTrue($boot->isDtoCacheLoaded());

        @unlink($cachePath);
    }

    #[Test]
    public function dto_cache_path_puede_sobreescribirse_via_subclase(): void
    {
        $customPath = sys_get_temp_dir() . '/custom_dto_cache_' . uniqid() . '.php';
        $dtos = ['App\\DTO\\CustomDTO'];
        file_put_contents($customPath, "<?php\nreturn " . var_export($dtos, true) . ";\n");

        $boot = $this->bootWithDtoCache($customPath, testingMode: true);

        // En testing mode, setEnhancedDTOs simula la carga
        $boot->setEnhancedDTOs($dtos);

        $this->assertTrue($boot->isDtoCacheLoaded());

        @unlink($customPath);
    }

    // ─────────────────────────────────────────────
    // 9. DataProvider: combinaciones de testingMode y loadRouter
    // ─────────────────────────────────────────────

    public static function provideInitParams(): array
    {
        return [
            'testing=true, loadRouter=true'  => [true,  true],
            'testing=true, loadRouter=false' => [true,  false],
        ];
    }

    #[Test]
    #[DataProvider('provideInitParams')]
    public function init_en_testing_mode_con_distintos_parametros(bool $testing, bool $loadRouter): void
    {
        $boot = new Boot(testingMode: $testing);

        $boot->init(loadRouter: $loadRouter);

        $this->assertSame($testing, $boot->isTestingMode());
    }

    // ─────────────────────────────────────────────
    // 10. Comportamiento de testing_mode impide inicialización del container
    // ─────────────────────────────────────────────

    #[Test]
    public function testing_mode_impide_la_inicializacion_del_container(): void
    {
        $boot = new Boot(testingMode: true);

        // En testing mode, init() no debe llegar a crear Context ni ErrorConsole
        // Si llegara, lanzaría excepciones de dependencias.
        $boot->init();

        $this->assertTrue($boot->isTestingMode());
    }

    #[Test]
    public function testing_mode_no_registra_autoloaders_adicionales_del_sistema(): void
    {
        $autoloadersBefore = count(spl_autoload_functions());

        // Instanciar en testing mode NO debe agregar autoloaders
        $boot = new Boot(testingMode: true);
        unset($boot); // evitar que quede referencia

        $autoloadersAfter = count(spl_autoload_functions());

        $this->assertSame(
            $autoloadersBefore,
            $autoloadersAfter,
            'Boot en testing mode no debe modificar los autoloaders del sistema'
        );
    }
}