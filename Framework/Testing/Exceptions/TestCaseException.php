<?php

/**
 * Balero CMS - TestCase Exception
 *
 * Excepción específica lanzada por Framework\Testing\TestCase cuando ocurre
 * un error durante la inicialización del contenedor de pruebas (setUp).
 *
 * @package Framework\Testing\Exceptions
 * @author Anibal Gomez
 * @license GNU General Public License
 */

namespace Framework\Testing\Exceptions;

use RuntimeException;

/**
 * TestCaseException
 *
 * Excepción tipada que se lanza cuando el setUp() de TestCase falla al
 * intentar instanciar la clase de contenedor especificada en el atributo
 * #[SetupTestContainer]. Extiende RuntimeException para mantener compatibilidad
 * con código que captura errores genéricos de runtime.
 *
 * Ejemplo de uso interno:
 * ```php
 * throw new TestCaseException(
 *     "Container class {$containerClass} does not exist",
 *     0,
 *     $e
 * );
 * ```
 */
class TestCaseException extends RuntimeException
{
    // Sin lógica adicional: la semántica específica está en el nombre de la clase.
}
