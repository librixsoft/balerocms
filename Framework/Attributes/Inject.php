<?php

namespace Framework\Attributes;

/**
 * Class Inject
 * Ejecuta inyeccion fuera del constructor
 * @package Framework\Attributes
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER)]
class Inject
{
    // Simplemente marca propiedades o parámetros para inyección automática
}
