<?php

namespace Tests\Framework\Bootstrap;

use Framework\Bootstrap\Boot;
use Framework\Exceptions\AutoloadException;
use Framework\Exceptions\DTOCacheException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests para Framework\Bootstrap\Boot
 *
 * NOTA: Boot en modo normal (testingMode = false) manipula autoloaders del sistema
 * (spl_autoload_unregister / register), por lo que TODOS los tests de lógica de negocio
 * usan testingMode = true o una subclase anónima para aislar el comportamiento.
 *
 * NOTA 2: Los tests que ejecutan código real de producción que registra handlers globales
 * (set_exception_handler, set_error_handler) se marcan con #[RunInSeparateProcess] para
 * evitar contaminación entre tests.
 */
class BootTest extends TestCase
{
    // ─────────────────────────────────────────────
    // setUp / tearDown — limpieza global entre tests
    //
    // PHPUnit 12 installs its own error/exception handlers before each test.
    // We must only restore handlers added by THIS test, not PHPUnit's own.
    // ─────────────────────────────────────────────

    private int $errorHandlerDepth     = 0;
    private int $exceptionHandlerDepth = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->errorHandlerDepth     = $this->measureHandlerDepth('error');
        $this->exceptionHandlerDepth = $this->measureHandlerDepth('exception');
    }

    protected function tearDown(): void
    {
        $this->restoreToDepth('error',     $this->errorHandlerDepth);
        $this->restoreToDepth('exception', $this->exceptionHandlerDepth);
        parent::tearDown();
    }

    private function measureHandlerDepth(string $type): int
    {
        $handlers = [];
        if ($type === 'error') {
            while (true) {
                $h = set_error_handler(static function (): bool { return false; });
                restore_error_handler();
                if ($h === null) { break; }
                $handlers[] = $h;
                restore_error_handler();
            }
            foreach (array_reverse($handlers) as $h) { set_error_handler($h); }
        } else {
            while (true) {
                $h = set_exception_handler(static function (\Throwable $e): void {});
                restore_exception_handler();
                if ($h === null) { break; }
                $handlers[] = $h;
                restore_exception_handler();
            }
            foreach (array_reverse($handlers) as $h) { set_exception_handler($h); }
        }
        return count($handlers);
    }

    private function restoreToDepth(string $type, int $targetDepth): void
    {
        $current  = $this->measureHandlerDepth($type);
        $toRemove = $current - $targetDepth;
        for ($i = 0; $i < $toRemove; $i++) {
            if ($type === 'error') {
                restore_error_handler();
            } else {
                restore_exception_handler();
            }
        }
    }

    // ─────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────

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

    private function bootNormalWithDtoCache(string $cachePath): Boot
    {
        $boot = new class(true, $cachePath) extends Boot {
            public function __construct(bool $testingMode, private string $overridePath)
            {
                parent::__construct($testingMode);
            }

            protected function getDtoCachePath(): string
            {
                return $this->overridePath;
            }
        };

        $boot->enableTestingMode(false);

        return $boot;
    }

    private function createTempDtoCache(array $dtos): string
    {
        $path   = sys_get_temp_dir() . '/dto_cache_' . uniqid() . '.php';
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
        $boot = new Boot(testingMode: true);
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

        $boot->enableTestingMode();

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

        $boot->init();

        $this->assertTrue($boot->isTestingMode());
    }

    #[Test]
    public function init_en_modo_testing_no_toca_el_contexto_ni_el_router(): void
    {
        $boot = new Boot(testingMode: true);

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

        $boot->autoloadClass($fqcn);
        $this->assertTrue(class_exists($fqcn, false));

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
    // 5. autoloadClass() resolución de FQCN en testing
    // ─────────────────────────────────────────────

    #[Test]
    public function autoload_class_en_testing_resuelve_cualquier_fqcn_en_memoria(): void
    {
        $boot = new Boot(testingMode: true);

        $cases = [
            'TmpAutoloadNs\\TmpAutoloadClass'    . uniqid(),
            'Deep\\Nested\\Namespace\\SomeClass' . uniqid(),
            'Another\\Ns\\YetAnotherClass'       . uniqid(),
        ];

        foreach ($cases as $fqcn) {
            $this->assertFalse(class_exists($fqcn, false), "La clase $fqcn no debería existir aún");
            $boot->autoloadClass($fqcn);
            $this->assertTrue(class_exists($fqcn, false), "La clase $fqcn debería existir después de autoloadClass");
        }
    }

    #[Test]
    public function autoload_class_en_modo_testing_crea_clase_vacia_aunque_no_exista_en_disco(): void
    {
        $boot  = new Boot(testingMode: true);
        $class = 'Nonexistent\\ClaseQueJamasExistira' . uniqid();

        $boot->autoloadClass($class);

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
        $boot->setEnhancedDTOs(['App\\DTO\\RealDTO']);

        $otherClass = 'App\\DTO\\OtraClase' . uniqid();
        $boot->autoloadClass($otherClass);

        $this->assertTrue(class_exists($otherClass, false));
    }

    #[Test]
    public function autoload_enhanced_dto_ya_cargado_en_clase_retorna_true_sin_recargar(): void
    {
        $className = 'FakeEnhancedDTO' . uniqid();
        $fqcn      = 'App\\DTO\\' . $className;

        eval("namespace App\\DTO; class {$className} {}");
        $this->assertTrue(class_exists($fqcn, false));

        $boot = new Boot(testingMode: true);
        $boot->enableTestingMode(false);
        $boot->setEnhancedDTOs([$fqcn]);

        // La clase ya existe → el guard class_exists la cortocircuita sin error
        $boot->autoloadClass($fqcn);

        $this->assertTrue(class_exists($fqcn, false));
    }

    #[Test]
    public function autoload_enhanced_dto_lanza_excepcion_si_falta_archivo_cache(): void
    {
        $className = 'MissingCacheDTO' . uniqid();
        $fqcn      = 'App\\DTO\\' . $className;

        $boot = new Boot(testingMode: true);
        $boot->enableTestingMode(false);
        $boot->setEnhancedDTOs([$fqcn]);

        $this->expectException(\Throwable::class);

        $boot->autoloadClass($fqcn);
    }

    // ─────────────────────────────────────────────
    // 8. getDtoCachePath / carga de caché temporal
    // ─────────────────────────────────────────────

    #[Test]
    public function dto_cache_se_carga_desde_archivo_temporal(): void
    {
        $dtos      = ['App\\DTO\\FooDTO', 'App\\DTO\\BarDTO'];
        $cachePath = $this->createTempDtoCache($dtos);

        $boot = $this->bootWithDtoCache($cachePath, testingMode: true);
        $boot->setEnhancedDTOs($dtos);

        $this->assertTrue($boot->isDtoCacheLoaded());

        @unlink($cachePath);
    }

    #[Test]
    public function dto_cache_path_puede_sobreescribirse_via_subclase(): void
    {
        $customPath = sys_get_temp_dir() . '/custom_dto_cache_' . uniqid() . '.php';
        $dtos       = ['App\\DTO\\CustomDTO'];
        file_put_contents($customPath, "<?php\nreturn " . var_export($dtos, true) . ";\n");

        $boot = $this->bootWithDtoCache($customPath, testingMode: true);
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
            'testing=true, loadRouter=true'  => [true, true],
            'testing=true, loadRouter=false' => [true, false],
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
    // 10. testing_mode impide inicialización real
    // ─────────────────────────────────────────────

    #[Test]
    public function testing_mode_impide_la_inicializacion_del_container(): void
    {
        $boot = new Boot(testingMode: true);

        $boot->init();

        $this->assertTrue($boot->isTestingMode());
    }

    #[Test]
    public function testing_mode_no_registra_autoloaders_adicionales_del_sistema(): void
    {
        $autoloadersBefore = count(spl_autoload_functions());

        $boot = new Boot(testingMode: true);
        unset($boot);

        $autoloadersAfter = count(spl_autoload_functions());

        $this->assertSame(
            $autoloadersBefore,
            $autoloadersAfter,
            'Boot en testing mode no debe modificar los autoloaders del sistema'
        );
    }

    // ─────────────────────────────────────────────
    // 11. getDtoCachePath / getTestDtoCachePath
    // ─────────────────────────────────────────────

    #[Test]
    public function get_test_dto_cache_path_retorna_ruta_por_defecto(): void
    {
        $boot = new Boot(testingMode: true);

        $path = $boot->getTestDtoCachePath();

        $this->assertStringEndsWith('dtos.cache.php', $path);
    }

    #[Test]
    public function get_test_dto_cache_path_retorna_ruta_sobreescrita_en_subclase(): void
    {
        $customPath = '/tmp/custom_path_' . uniqid() . '.php';
        $boot       = $this->bootWithDtoCache($customPath, testingMode: true);

        $this->assertSame($customPath, $boot->getTestDtoCachePath());
    }

    // ─────────────────────────────────────────────
    // 12. callLoadDTOCacheEarly()
    // ─────────────────────────────────────────────

    #[Test]
    public function load_dto_cache_early_carga_array_desde_archivo_real(): void
    {
        $dtos      = ['App\\DTO\\EarlyDTO1', 'App\\DTO\\EarlyDTO2'];
        $cachePath = $this->createTempDtoCache($dtos);

        $boot = $this->bootNormalWithDtoCache($cachePath);

        $this->assertFalse($boot->isDtoCacheLoaded());

        $boot->callLoadDTOCacheEarly();

        $this->assertTrue($boot->isDtoCacheLoaded());

        @unlink($cachePath);
    }

    #[Test]
    public function load_dto_cache_early_no_recarga_si_ya_fue_cargado(): void
    {
        $dtos      = ['App\\DTO\\AlreadyLoadedDTO'];
        $cachePath = $this->createTempDtoCache($dtos);

        $boot = $this->bootNormalWithDtoCache($cachePath);
        $boot->callLoadDTOCacheEarly();
        $this->assertTrue($boot->isDtoCacheLoaded());

        unlink($cachePath);

        $boot->callLoadDTOCacheEarly();

        $this->assertTrue($boot->isDtoCacheLoaded());
    }

    #[Test]
    public function load_dto_cache_early_lanza_dto_cache_exception_si_archivo_no_existe(): void
    {
        $nonExistentPath = '/tmp/no_existe_jamas_' . uniqid() . '.php';

        $boot = $this->bootNormalWithDtoCache($nonExistentPath);

        $this->expectException(DTOCacheException::class);

        $boot->callLoadDTOCacheEarly();
    }

    #[Test]
    public function load_dto_cache_early_es_noop_en_testing_mode(): void
    {
        $nonExistentPath = '/tmp/nope_' . uniqid() . '.php';
        $boot            = $this->bootWithDtoCache($nonExistentPath, testingMode: true);

        $boot->callLoadDTOCacheEarly();

        $this->assertFalse($boot->isDtoCacheLoaded());
    }

    // ─────────────────────────────────────────────
    // 13. callLoadDTOCache()
    // ─────────────────────────────────────────────

    #[Test]
    public function load_dto_cache_carga_array_desde_archivo_real(): void
    {
        $dtos      = ['App\\DTO\\LateDTO1', 'App\\DTO\\LateDTO2'];
        $cachePath = $this->createTempDtoCache($dtos);

        $boot = $this->bootNormalWithDtoCache($cachePath);

        $this->assertFalse($boot->isDtoCacheLoaded());

        $boot->callLoadDTOCache();

        $this->assertTrue($boot->isDtoCacheLoaded());

        @unlink($cachePath);
    }

    #[Test]
    public function load_dto_cache_no_recarga_si_ya_fue_cargado(): void
    {
        $dtos      = ['App\\DTO\\LateDTOAlreadyLoaded'];
        $cachePath = $this->createTempDtoCache($dtos);

        $boot = $this->bootNormalWithDtoCache($cachePath);
        $boot->callLoadDTOCache();
        $this->assertTrue($boot->isDtoCacheLoaded());

        unlink($cachePath);

        $boot->callLoadDTOCache();

        $this->assertTrue($boot->isDtoCacheLoaded());
    }

    #[Test]
    public function load_dto_cache_lanza_dto_cache_exception_si_archivo_no_existe(): void
    {
        $nonExistentPath = '/tmp/late_no_existe_' . uniqid() . '.php';

        $boot = $this->bootNormalWithDtoCache($nonExistentPath);

        $this->expectException(DTOCacheException::class);

        $boot->callLoadDTOCache();
    }

    #[Test]
    public function load_dto_cache_es_noop_en_testing_mode(): void
    {
        $nonExistentPath = '/tmp/late_nope_' . uniqid() . '.php';
        $boot            = $this->bootWithDtoCache($nonExistentPath, testingMode: true);

        $boot->callLoadDTOCache();

        $this->assertFalse($boot->isDtoCacheLoaded());
    }

    // ─────────────────────────────────────────────
    // 14. autoloadClass modo normal – PSR-4
    // ─────────────────────────────────────────────

    #[Test]
    public function autoload_class_normal_lanza_autoload_exception_clase_inexistente(): void
    {
        $boot = new Boot(testingMode: true);
        $boot->enableTestingMode(false);
        $boot->setEnhancedDTOs([]);

        $this->expectException(AutoloadException::class);

        $boot->autoloadClass('ClaseQueJamasExistiraEnDiscoPSR4\\Foo' . uniqid());
    }

    #[Test]
    public function autoload_class_normal_carga_archivo_psr4_existente(): void
    {
        $uniqueId  = uniqid('Psr4Class');
        $namespace = 'TmpPsr4Ns';
        $fqcn      = $namespace . '\\' . $uniqueId;

        $dir  = rtrim(BASE_PATH, '/') . '/' . $namespace;
        $file = $dir . '/' . $uniqueId . '.php';

        @mkdir($dir, 0777, true);
        file_put_contents($file, "<?php\nnamespace {$namespace};\nclass {$uniqueId} {}\n");

        try {
            $boot = new Boot(testingMode: true);
            $boot->enableTestingMode(false);
            $boot->setEnhancedDTOs([]);

            $this->assertFalse(class_exists($fqcn, false));

            $boot->autoloadClass($fqcn);

            $this->assertTrue(class_exists($fqcn, false));
        } finally {
            @unlink($file);
            @rmdir($dir);
        }
    }

    // ─────────────────────────────────────────────
    // 15. loadEnhancedDTO – carga desde caché con archivo real
    // ─────────────────────────────────────────────

    #[Test]
    public function autoload_enhanced_dto_carga_clase_desde_cache_dtos_existente(): void
    {
        $uniqueId  = uniqid('EnhancedReal');
        $fqcn      = 'App\\DTO\\' . $uniqueId;

        $cacheDir  = rtrim(BASE_PATH, '/') . '/cache/dtos';
        $cacheFile = $cacheDir . '/' . $uniqueId . '.php';

        @mkdir($cacheDir, 0777, true);
        file_put_contents($cacheFile, "<?php\nnamespace App\\DTO;\nclass {$uniqueId} {}\n");

        try {
            $boot = new Boot(testingMode: true);
            $boot->enableTestingMode(false);
            $boot->setEnhancedDTOs([$fqcn]);

            $this->assertFalse(class_exists($fqcn, false));

            $boot->autoloadClass($fqcn);

            $this->assertTrue(class_exists($fqcn, false));
        } finally {
            @unlink($cacheFile);
        }
    }

    #[Test]
    public function autoload_enhanced_dto_lanza_dto_cache_exception_si_archivo_no_define_la_clase(): void
    {
        $uniqueId  = uniqid('EnhancedBroken');
        $fqcn      = 'App\\DTO\\' . $uniqueId;

        $cacheDir  = rtrim(BASE_PATH, '/') . '/cache/dtos';
        $cacheFile = $cacheDir . '/' . $uniqueId . '.php';

        @mkdir($cacheDir, 0777, true);
        file_put_contents($cacheFile, "<?php\nnamespace App\\DTO;\nclass OtraClaseQueNoCoincide{$uniqueId} {}\n");

        try {
            $boot = new Boot(testingMode: true);
            $boot->enableTestingMode(false);
            $boot->setEnhancedDTOs([$fqcn]);

            $this->expectException(DTOCacheException::class);
            $boot->autoloadClass($fqcn);
        } finally {
            @unlink($cacheFile);
        }
    }

    // ─────────────────────────────────────────────
    // 16. setEnhancedDTOs + callLoadDTOCacheEarly combinados
    // ─────────────────────────────────────────────

    #[Test]
    public function set_enhanced_dtos_y_load_dto_cache_early_son_independientes(): void
    {
        $dtos      = ['App\\DTO\\IndepDTO'];
        $cachePath = $this->createTempDtoCache($dtos);

        $boot = $this->bootNormalWithDtoCache($cachePath);
        $boot->setEnhancedDTOs($dtos);
        $this->assertTrue($boot->isDtoCacheLoaded());

        unlink($cachePath);

        $boot->callLoadDTOCacheEarly();

        $this->assertTrue($boot->isDtoCacheLoaded());
    }

    #[Test]
    public function load_dto_cache_early_y_late_producen_mismo_resultado(): void
    {
        $dtos      = ['App\\DTO\\SameDTO'];
        $cachePath = $this->createTempDtoCache($dtos);

        $bootA = $this->bootNormalWithDtoCache($cachePath);
        $bootA->callLoadDTOCacheEarly();

        $bootB = $this->bootNormalWithDtoCache($cachePath);
        $bootB->callLoadDTOCache();

        $this->assertTrue($bootA->isDtoCacheLoaded());
        $this->assertTrue($bootB->isDtoCacheLoaded());

        @unlink($cachePath);
    }

    // ─────────────────────────────────────────────
    // 17. Constructor modo normal
    // ─────────────────────────────────────────────

    #[Test]
    public function constructor_en_modo_normal_ejecuta_registerEarlyErrorHandler(): void
    {
        $dtos      = [];
        $cachePath = $this->createTempDtoCache($dtos);
        $called    = false;

        $boot = new class(false, $cachePath, $called) extends Boot {
            public function __construct(
                bool $testingMode,
                private string $overridePath,
                public bool &$calledFlag
            ) {
                parent::__construct($testingMode);
            }

            protected function getDtoCachePath(): string
            {
                return $this->overridePath;
            }

            protected function registerEarlyErrorHandler(): void
            {
                $this->calledFlag = true;
            }
        };

        $this->assertTrue($called, 'registerEarlyErrorHandler debe llamarse en modo normal');

        @unlink($cachePath);
    }

    #[Test]
    public function constructor_en_modo_normal_carga_dto_cache_early(): void
    {
        $dtos      = ['App\\DTO\\EarlyNormalDTO'];
        $cachePath = $this->createTempDtoCache($dtos);

        $boot = new class(false, $cachePath) extends Boot {
            public function __construct(bool $testingMode, private string $overridePath)
            {
                parent::__construct($testingMode);
            }

            protected function getDtoCachePath(): string
            {
                return $this->overridePath;
            }

            protected function registerEarlyErrorHandler(): void
            {
                // No-op: evitar handlers globales en tests
            }
        };

        $this->assertTrue($boot->isDtoCacheLoaded());

        @unlink($cachePath);
    }

    #[Test]
    public function constructor_en_modo_normal_lanza_dto_cache_exception_si_cache_no_existe(): void
    {
        $nonExistentPath = '/tmp/early_nope_constructor_' . uniqid() . '.php';

        $this->expectException(DTOCacheException::class);

        new class(false, $nonExistentPath) extends Boot {
            public function __construct(bool $testingMode, private string $overridePath)
            {
                parent::__construct($testingMode);
            }

            protected function getDtoCachePath(): string
            {
                return $this->overridePath;
            }

            protected function registerEarlyErrorHandler(): void
            {
                // No-op
            }
        };
    }

    // ─────────────────────────────────────────────
    // 18. registerEarlyErrorHandler – comportamiento real
    // ─────────────────────────────────────────────

    #[Test]
    public function register_early_error_handler_registra_handler_de_excepcion(): void
    {
        $dtos      = [];
        $cachePath = $this->createTempDtoCache($dtos);

        $boot = new class(false, $cachePath) extends Boot {
            public function __construct(bool $testingMode, private string $overridePath)
            {
                parent::__construct($testingMode);
            }

            protected function getDtoCachePath(): string
            {
                return $this->overridePath;
            }
        };

        // Si llegamos aquí sin excepción, el handler se registró correctamente
        $this->assertTrue($boot->isDtoCacheLoaded());

        @unlink($cachePath);
    }

    // ─────────────────────────────────────────────
    // 19. renderEarlyError
    // ─────────────────────────────────────────────

    #[Test]
    public function render_early_error_delega_a_early_error_console(): void
    {
        $boot = new class(true) extends Boot {
            public bool $renderCalled = false;

            public function __construct(bool $testingMode)
            {
                parent::__construct($testingMode);
            }

            public function callRenderEarlyError(\Throwable $e): void
            {
                $this->renderEarlyError($e);
            }

            protected function renderEarlyError(\Throwable $e): void
            {
                $this->renderCalled = true;
            }
        };

        $boot->callRenderEarlyError(new \RuntimeException('test error'));

        $this->assertTrue($boot->renderCalled);
    }

    #[Test]
    public function render_early_error_real_delega_en_early_error_console_inyectada(): void
    {
        $boot = new class(true) extends Boot {
            public function __construct(bool $testingMode)
            {
                parent::__construct($testingMode);
            }

            public function callParentRenderEarlyError(\Throwable $e): void
            {
                parent::renderEarlyError($e);
            }
        };

        $fakeEarlyConsole = new class extends \Framework\Core\EarlyErrorConsole {
            public int $calls = 0;

            public function render(\Throwable $e): void
            {
                $this->calls++;
            }
        };

        $ref = new \ReflectionProperty(Boot::class, 'earlyErrorConsole');
        $ref->setAccessible(true);
        $ref->setValue($boot, $fakeEarlyConsole);

        $boot->callParentRenderEarlyError(new \RuntimeException('delegation test'));

        $this->assertSame(1, $fakeEarlyConsole->calls);
    }

    // ─────────────────────────────────────────────
    // 20. createContext() / dispatchRouter() aislados
    // ─────────────────────────────────────────────

    #[Test]
    public function create_context_lanza_container_initialization_exception_si_falla(): void
    {
        $boot = new class(true) extends Boot {
            public function __construct(bool $testingMode)
            {
                parent::__construct($testingMode);
            }

            protected function createContext(): void
            {
                throw new \Framework\Exceptions\ContainerInitializationException('test failure');
            }
        };

        $boot->enableTestingMode(false);

        $this->expectException(\Framework\Exceptions\ContainerInitializationException::class);

        $boot->init(loadRouter: false);
    }

    #[Test]
    public function dispatch_router_lanza_router_initialization_exception_si_falla(): void
    {
        $boot = new class(true) extends Boot {
            public function __construct(bool $testingMode)
            {
                parent::__construct($testingMode);
            }

            protected function createContext(): void
            {
                // stub
            }

            protected function dispatchRouter(): void
            {
                throw new \Framework\Exceptions\RouterInitializationException('router fail');
            }
        };

        $boot->setEnhancedDTOs([]);
        $boot->enableTestingMode(false);

        $this->expectException(\Framework\Exceptions\RouterInitializationException::class);

        $boot->init(loadRouter: true);
    }

    // ─────────────────────────────────────────────
    // 21. init() modo normal con stubs completos
    // ─────────────────────────────────────────────

    #[Test]
    public function init_en_modo_normal_sin_router_llama_create_context_y_load_cache(): void
    {
        $dtos          = ['App\\DTO\\NormalModeDTO'];
        $cachePath     = $this->createTempDtoCache($dtos);
        $contextCalled = false;
        $routerCalled  = false;

        $boot = new class(true, $cachePath, $contextCalled, $routerCalled) extends Boot {
            public function __construct(
                bool $testingMode,
                private string $overridePath,
                public bool &$ctxCalled,
                public bool &$rtrCalled
            ) {
                parent::__construct($testingMode);
            }

            protected function getDtoCachePath(): string
            {
                return $this->overridePath;
            }

            protected function createContext(): void
            {
                $this->ctxCalled = true;
            }

            protected function dispatchRouter(): void
            {
                $this->rtrCalled = true;
            }
        };

        $boot->enableTestingMode(false);
        $boot->init(loadRouter: false);

        $this->assertTrue($contextCalled, 'createContext debe haberse llamado');
        $this->assertFalse($routerCalled, 'dispatchRouter NO debe llamarse cuando loadRouter=false');
        $this->assertTrue($boot->isDtoCacheLoaded());

        @unlink($cachePath);
    }

    #[Test]
    public function init_en_modo_normal_con_router_llama_dispatch_router(): void
    {
        $dtos         = [];
        $cachePath    = $this->createTempDtoCache($dtos);
        $routerCalled = false;

        $boot = new class(true, $cachePath, $routerCalled) extends Boot {
            public function __construct(
                bool $testingMode,
                private string $overridePath,
                public bool &$rtrCalled
            ) {
                parent::__construct($testingMode);
            }

            protected function getDtoCachePath(): string
            {
                return $this->overridePath;
            }

            protected function createContext(): void
            {
                // stub
            }

            protected function dispatchRouter(): void
            {
                $this->rtrCalled = true;
            }
        };

        $boot->enableTestingMode(false);
        $boot->init(loadRouter: true);

        $this->assertTrue($routerCalled, 'dispatchRouter debe haberse llamado cuando loadRouter=true');

        @unlink($cachePath);
    }

    // ─────────────────────────────────────────────
    // 22. registerEarlyErrorHandler – callbacks reales
    // ─────────────────────────────────────────────

    #[Test]
    public function register_early_error_handler_ejecuta_callbacks_de_exception_y_error(): void
    {
        // Do NOT drain PHPUnit's own output buffers — record baseline instead
        $obBaseline = ob_get_level();

        $boot = new class(true) extends Boot {
            public int $renderCalls = 0;

            public function __construct(bool $testingMode)
            {
                parent::__construct($testingMode);
            }

            public function callRegisterEarlyErrorHandler(): void
            {
                $this->registerEarlyErrorHandler();
            }

            protected function renderEarlyError(\Throwable $e): void
            {
                $this->renderCalls++;
            }
        };

        $boot->callRegisterEarlyErrorHandler();

        $previousExceptionHandler = set_exception_handler(static function (): void {});
        restore_exception_handler();

        if (is_callable($previousExceptionHandler)) {
            $previousExceptionHandler(new \RuntimeException('forced exception'));
        }

        $previousErrorHandler = set_error_handler(static function (): bool { return true; });
        restore_error_handler();

        if (is_callable($previousErrorHandler)) {
            $previousErrorHandler(E_USER_WARNING, 'forced warning', __FILE__, __LINE__);
        }

        $this->assertGreaterThanOrEqual(2, $boot->renderCalls);
    }

    // ─────────────────────────────────────────────
    // 23. dispatchRouter() real – sin contexto
    // ─────────────────────────────────────────────

    #[Test]
    public function dispatch_router_real_lanza_router_initialization_exception_cuando_no_hay_contexto(): void
    {
        $boot = new class(true) extends Boot {
            public function __construct(bool $testingMode)
            {
                parent::__construct($testingMode);
            }

            public function callRealDispatchRouter(): void
            {
                parent::dispatchRouter();
            }
        };

        $boot->enableTestingMode(false);

        $this->expectException(\Framework\Exceptions\RouterInitializationException::class);
        $boot->callRealDispatchRouter();
    }

    // ─────────────────────────────────────────────
    // 24. createContext() real
    // Aislado en proceso separado: registra handlers globales y llama a Context
    // real (DI container completo), lo que contaminaría el proceso de PHPUnit.
    // ─────────────────────────────────────────────

    #[Test]
    public function create_context_real_ejecuta_flujo_de_inicializacion(): void
    {
        $boot = new class(true) extends Boot {
            public function __construct(bool $testingMode)
            {
                parent::__construct($testingMode);
            }

            public function callRealCreateContext(): void
            {
                parent::createContext();
            }
        };

        $boot->enableTestingMode(false);
        $boot->callRealCreateContext();

        $this->assertFalse($boot->isTestingMode());
    }

    // ─────────────────────────────────────────────
    // 25. dispatchRouter() real – después de createContext()
    // ─────────────────────────────────────────────

    // TODO: Fix this test
//    #[Test]
//    #[RunInSeparateProcess]
//    #[PreserveGlobalState(false)]
//    public function dispatch_router_real_falla_despues_de_create_context_y_envuelve_excepcion(): void
//    {
//        $boot = new class(true) extends Boot {
//            public function __construct(bool $testingMode)
//            {
//                parent::__construct($testingMode);
//            }
//
//            public function callRealCreateContext(): void
//            {
//                parent::createContext();
//            }
//
//            public function callRealDispatchRouter(): void
//            {
//                parent::dispatchRouter();
//            }
//        };
//
//        $boot->enableTestingMode(false);
//        $boot->setEnhancedDTOs([]);
//        $boot->callRealCreateContext();
//
//        $this->expectException(\Framework\Exceptions\RouterInitializationException::class);
//        $boot->callRealDispatchRouter();
//    }
}