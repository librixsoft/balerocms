<?php

use Framework\Static\Flash;
use PHPUnit\Framework\TestCase;

class FlashTest extends TestCase
{
    /**
     * Verifica que los valores guardados en Flash con set()
     * puedan recuperarse correctamente usando get(),
     * y que has() confirme su existencia.
     */
    public function testSetStoresValueAndGetRetrievesIt(): void
    {
        Flash::set('username', 'user-anibal');

        $this->assertTrue(Flash::has('username'));
        $this->assertEquals('user-anibal', Flash::get('username'));
    }

    /**
     * Verifica que get() retorne el valor por defecto
     * cuando la clave solicitada no existe en la sesión flash.
     */
    public function testGetReturnsDefaultWhenKeyDoesNotExist(): void
    {
        $this->assertFalse(Flash::has('non-existent-key'));
        $this->assertEquals(
            'default-value',
            Flash::get('non-existent-key', 'default-value')
        );
    }

    /**
     * Verifica que clear() elimine todos los valores
     * almacenados en la sesión flash.
     */
    public function testClearRemovesAllFlashData(): void
    {
        Flash::set('token', 'abc123');
        $this->assertTrue(Flash::has('token'));

        Flash::clear();

        $this->assertFalse(Flash::has('token'));
    }

    /**
     * Verifica que delete() pueda eliminar una clave unica (clave.valor)
     * dentro de un array flash utilizando su forma "aplanada".
     */
    public function testDeleteRemovesOnlySpecifiedFlatKey(): void
    {
        Flash::set('errors', [
            'username' => 'Username is required.',
            'email' => 'Email is invalid.',
        ]);

        $this->assertTrue(Flash::has('errors'));

        Flash::delete('errors.username'); // eliminar solo la clave "username"

        $errors = Flash::get('errors');

        $this->assertArrayNotHasKey('username', $errors);
        $this->assertArrayHasKey('email', $errors);
    }

    /**
     * Verifica que delete() sea seguro de usar incluso
     * cuando se intenta eliminar una clave que no existe.
     */
    public function testDeleteDoesNothingWhenKeyDoesNotExist(): void
    {
        Flash::set('errors', [
            'username' => 'Username is required.'
        ]);

        Flash::delete('errors.password'); // no existe, no debe fallar

        $errors = Flash::get('errors');

        $this->assertArrayHasKey('username', $errors);
        $this->assertEquals(
            'Username is required.',
            $errors['username']
        );
    }

    /**
     * verifica que Flash pueda guardar y recuperar valores simples
     * usando set() y get(), y que delete() pueda borrar valores simples directamente.
     */
    public function testSimpleValueSetGetAndDelete(): void
    {
        // guardar valores simples
        Flash::set('email', 'test@example.com'); // valor simple
        Flash::set('flag', true); // valor simple tipo flag

        $this->assertTrue(Flash::has('email'));
        $this->assertEquals('test@example.com', Flash::get('email'));

        $this->assertTrue(Flash::has('flag'));
        $this->assertTrue(Flash::get('flag'));

        // borrar valores simples
        Flash::delete('email'); // eliminar valor simple
        Flash::delete('flag');  // eliminar valor simple

        $this->assertFalse(Flash::has('email'));
        $this->assertFalse(Flash::has('flag'));
    }

    /**
     * Verifica que se puedan almacenar tanto arrays como valores simples
     * en Flash, recuperarlos y luego eliminarlos correctamente.
     */
    public function testArrayAndSimpleValueInFlash(): void
    {
        // Agregar array al flash
        Flash::set('errors', [
            'username' => 'Username is required.',
            'email' => 'Email is invalid.',
        ]);

        // Agregar valor simple al flash
        Flash::set('email', 'user@example.com');

        // Comprobamos existencia
        $this->assertTrue(Flash::has('errors'));
        $this->assertTrue(Flash::has('email'));

        // Comprobamos valores
        $errors = Flash::get('errors');
        $this->assertArrayHasKey('username', $errors);
        $this->assertArrayHasKey('email', $errors);

        $this->assertEquals('user@example.com', Flash::get('email'));

        // Eliminar un valor del array
        Flash::delete('errors.username');
        $errors = Flash::get('errors');
        $this->assertArrayNotHasKey('username', $errors);
        $this->assertArrayHasKey('email', $errors);

        // Eliminar valor simple
        Flash::delete('email');
        $this->assertFalse(Flash::has('email'));
    }

    /**
     * Reinicia la sesión antes de cada prueba para garantizar
     * un entorno limpio y evitar fugas de estado entre tests.
     */
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = []; // limpiar la sesión manualmente
    }

}
