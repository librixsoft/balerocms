<?php

namespace Framework\Http;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class Auth
{
    public function __construct(
        public bool $required = true
    )
    {
    }
}
