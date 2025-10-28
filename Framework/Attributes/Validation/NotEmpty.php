<?php

namespace Framework\Attributes\Validation;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class NotEmpty
{
    public function __construct(public string $message = 'Field cannot be empty') {}
}