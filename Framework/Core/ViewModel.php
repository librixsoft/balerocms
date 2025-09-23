<?php

namespace Framework\Core;

class ViewModel
{
    /**
     * Contenedor interno de parámetros
     */
    private array $data = [];

    /**
     * Agrega un solo parámetro
     */
    public function add(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Agrega múltiples parámetros de una sola vez
     */
    public function addAll(array $params): void
    {
        $this->data = array_merge($this->data, $params);
    }

    /**
     * Devuelve todos los parámetros
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Limpia todos los parámetros
     */
    public function clear(): void
    {
        $this->data = [];
    }
}
