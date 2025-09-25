<?php

namespace Framework\Core;

class ViewModel
{
    private array $data = [];

    public function add(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function addAll(array $params): void
    {
        $this->data = array_merge($this->data, $params);
    }

    public function all(): array
    {
        return $this->data;
    }

    public function clear(): void
    {
        $this->data = [];
    }
}
