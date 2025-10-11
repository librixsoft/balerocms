<?php

namespace Framework\Attributes;

#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::TARGET_PROPERTY)]
class FlashStorage
{
    public function __construct(public string $key) {}
}