<?php

namespace Framework\Core;

use Exception;

class JSONHandler
{
    private string $file;
    private array $data = [];

    public function __construct(string $jsonFile)
    {
        if (!file_exists($jsonFile)) {
            throw new Exception("Archivo no encontrado: " . $jsonFile);
        }

        $this->file = $jsonFile;
        $this->readJSON();
    }

    private function readJSON(): void
    {
        $content = file_get_contents($this->file);
        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Error cargando JSON: " . json_last_error_msg());
        }

        $this->data = $decoded;
    }

    public function get(string $path): string
    {
        $keys = explode('/', trim($path, '/'));
        $value = $this->data;

        foreach ($keys as $key) {
            if (!isset($value[$key])) {
                return '';
            }
            $value = $value[$key];
        }

        return (string)$value;
    }

    public function set(string $path, string $value): void
    {
        $keys = explode('/', trim($path, '/'));
        $ref = &$this->data;

        foreach ($keys as $key) {
            if (!isset($ref[$key]) || !is_array($ref[$key])) {
                $ref[$key] = [];
            }
            $ref = &$ref[$key];
        }

        $ref = $value;

        $this->save();
    }

    public function save(): void
    {
        $json = json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if (file_put_contents($this->file, $json) === false) {
            throw new Exception("No se pudo guardar el archivo JSON: " . $this->file);
        }
    }
}
