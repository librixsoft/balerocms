<?php

namespace Framework\Attributes\Validation;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Email
{
    public function __construct(public string $message = 'Invalid email address') {}
}