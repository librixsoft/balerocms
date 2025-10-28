<?php

namespace Framework\Attributes\Validation;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class FieldMatch
{
    public function __construct(
        public string $field,
        public string $message = 'Fields do not match'
    ) {}
}