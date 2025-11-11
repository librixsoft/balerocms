<?php

namespace Framework\Core;

use Framework\Exceptions\JSONHandlerException;

class JSONHandler
{
    private string $file;
    private array $data = [];

    public function __construct(string $jsonFile, ?string $initialContent = null)
    {
        $this->file = $jsonFile;

        // Permite pasar contenido inicial sin requerir archivo real (para tests)
        if ($initialContent !== null) {
            $decoded = json_decode($initialContent, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new JSONHandlerException("Error parsing JSON: " . json_last_error_msg());
            }
            $this->data = $decoded;
            return;
        }

        if (!file_exists($jsonFile)) {
            throw new JSONHandlerException("File not found: " . $jsonFile);
        }

        $this->readJSON();
    }

    private function readJSON(): void
    {
        $content = file_get_contents($this->file);
        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new JSONHandlerException("Error parsing JSON: " . json_last_error_msg());
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
        $json = json_encode(
            $this->data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if (file_put_contents($this->file, $json) === false) {
            throw new JSONHandlerException("Could not save JSON file: " . $this->file);
        }
    }

    public function getData(): array
    {
        return $this->data;
    }
}
