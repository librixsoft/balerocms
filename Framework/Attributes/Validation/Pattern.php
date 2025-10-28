<?php

namespace Framework\Attributes\Validation;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Pattern
{
    public function __construct(
        public string $regex,
        public string $message = 'Invalid format'
    ) {}
}