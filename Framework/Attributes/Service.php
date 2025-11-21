<?php

namespace Framework\Attributes;

use Attribute;

/**
 * Marca una clase como servicio de la aplicación.
 *
 * Este atributo es puramente documental y ayuda a identificar
 * las clases de servicio en la capa de negocio.
 *
 * El Container resuelve automáticamente todas las dependencias
 * sin necesidad de configuración adicional.
 *
 * @author Anibal Gomez
 * @license GNU General Public License
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Service
{
}