<?php

namespace Framework\Attributes\Validation;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class NotBlank
{
    public function __construct(
        public string $message = 'This field cannot be blank'
    ) {}
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class Email
{
    public function __construct(
        public string $message = 'Invalid email address'
    ) {}
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class Length
{
    public function __construct(
        public ?int $min = null,
        public ?int $max = null,
        public ?string $minMessage = null,
        public ?string $maxMessage = null
    ) {
        $this->minMessage = $minMessage ?? "Minimum length is {$min} characters";
        $this->maxMessage = $maxMessage ?? "Maximum length is {$max} characters";
    }
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class EqualTo
{
    public function __construct(
        public string $field,
        public string $message = 'Fields do not match'
    ) {}
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class Pattern
{
    public function __construct(
        public string $regex,
        public string $message = 'Invalid format'
    ) {}
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class Range
{
    public function __construct(
        public ?float $min = null,
        public ?float $max = null,
        public string $message = 'Value out of range'
    ) {}
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class Url
{
    public function __construct(
        public string $message = 'Invalid URL format'
    ) {}
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class Numeric
{
    public function __construct(
        public string $message = 'Must be a numeric value'
    ) {}
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class Integer
{
    public function __construct(
        public string $message = 'Must be an integer value'
    ) {}
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class Positive
{
    public function __construct(
        public string $message = 'Must be a positive number'
    ) {}
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class AlphaNumeric
{
    public function __construct(
        public string $message = 'Only letters and numbers are allowed'
    ) {}
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class Min
{
    public function __construct(
        public float $value,
        public string $message = 'Value is too small'
    ) {}
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class Max
{
    public function __construct(
        public float $value,
        public string $message = 'Value is too large'
    ) {}
}