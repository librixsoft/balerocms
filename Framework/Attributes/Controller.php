<?php

namespace Framework\Attributes;

use Attribute;

/**
 * Indica que una clase es un Controller y debe ser inicializada automáticamente
 * por el contenedor (por ejemplo, llamar initControllerAndRoute).
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Controller
{
    public function __construct(

    ) {}
}
