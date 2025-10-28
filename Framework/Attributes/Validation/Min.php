<?php

namespace Framework\Attributes\Validation;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Min
{
    public function __construct(
        public int $value,
        public string $message = 'Value is too short'
    ) {}
}