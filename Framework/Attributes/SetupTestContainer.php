<?php

namespace Framework\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class SetupTestContainer
{
    public function __construct(
        public ?string $containerClass = null
    ) {}
}