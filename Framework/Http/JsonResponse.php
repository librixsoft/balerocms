<?php

namespace Framework\Http;

use Attribute;

/**
 * Indica que el valor de retorno de un método
 * debe ser serializado a JSON con la cabecera adecuada.
 */
#[Attribute(Attribute::TARGET_METHOD)]
class JsonResponse
{
    // No necesita lógica interna, solo su existencia.
}