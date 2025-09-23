<?php

/**
 * Balero CMS
 * @author Anibal Gomez <balerocms@gmail.com>
 * @license GNU General Public License
 */

namespace Framework\Core;

class Validator
{
    protected array $data;
    protected array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function make(array $data): self
    {
        return new self($data);
    }

    public function required(string $field, ?string $message = null): self
    {
        if (empty($this->data[$field])) {
            $this->errors[$field] = $message ?? "El campo $field es obligatorio.";
        }

        return $this;
    }

    public function email(string $field, ?string $message = null): self
    {
        if (!empty($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = $message ?? "El campo $field debe ser un email válido.";
        }

        return $this;
    }

    public function match(string $field1, string $field2, ?string $message = null): self
    {
        if (($this->data[$field1] ?? null) !== ($this->data[$field2] ?? null)) {
            $this->errors[$field1] = $message ?? "Los campos $field1 y $field2 no coinciden.";
        }

        return $this;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
