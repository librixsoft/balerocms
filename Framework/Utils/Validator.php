<?php

namespace Framework\Utils;

use Framework\Attributes\Validation\Email;
use Framework\Attributes\Validation\FieldMatch;
use Framework\Attributes\Validation\Max;
use Framework\Attributes\Validation\Min;
use Framework\Attributes\Validation\NotEmpty;
use Framework\Attributes\Validation\Pattern;
use ReflectionClass;
use ReflectionProperty;

class Validator
{
    protected array $errors = [];

    public function validate(object $dto): bool
    {
        $this->errors = [];
        $reflection = new ReflectionClass($dto);

        foreach ($reflection->getProperties() as $property) {
            $this->validateProperty($property, $dto);
        }

        return empty($this->errors);
    }

    private function validateProperty(ReflectionProperty $property, object $dto): void
    {
        $property->setAccessible(true);
        $value = $property->getValue($dto);
        $propertyName = $property->getName();

        $attributes = $property->getAttributes();

        foreach ($attributes as $attribute) {
            $constraint = $attribute->newInstance();

            if ($constraint instanceof NotEmpty) {
                $this->validateNotEmpty($propertyName, $value, $constraint);
            } elseif ($constraint instanceof Email) {
                $this->validateEmail($propertyName, $value, $constraint);
            } elseif ($constraint instanceof FieldMatch) {
                $this->validateMatchFields($propertyName, $value, $constraint, $dto);
            } elseif ($constraint instanceof Min) {
                $this->validateMin($propertyName, $value, $constraint);
            } elseif ($constraint instanceof Max) {
                $this->validateMax($propertyName, $value, $constraint);
            } elseif ($constraint instanceof Pattern) {
                $this->validatePattern($propertyName, $value, $constraint);
            }
        }
    }

    private function validateNotEmpty(string $field, mixed $value, NotEmpty $constraint): void
    {
        if (empty($value) && $value !== '0') {
            $this->errors[$field] = $constraint->message;
        }
    }

    private function validateEmail(string $field, mixed $value, Email $constraint): void
    {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = $constraint->message;
        }
    }

    private function validateMatchFields(string $field, mixed $value, FieldMatch $constraint, object $dto): void
    {
        $reflection = new ReflectionClass($dto);
        $otherProperty = $reflection->getProperty($constraint->field);
        $otherProperty->setAccessible(true);
        $otherValue = $otherProperty->getValue($dto);

        if ($value !== $otherValue) {
            $this->errors[$field] = $constraint->message;
        }
    }

    private function validateMin(string $field, mixed $value, Min $constraint): void
    {
        if (!empty($value) && strlen((string)$value) < $constraint->value) {
            $this->errors[$field] = $constraint->message;
        }
    }

    private function validateMax(string $field, mixed $value, Max $constraint): void
    {
        if (!empty($value) && strlen((string)$value) > $constraint->value) {
            $this->errors[$field] = $constraint->message;
        }
    }

    private function validatePattern(string $field, mixed $value, Pattern $constraint): void
    {
        if (!empty($value) && !preg_match($constraint->regex, (string)$value)) {
            $this->errors[$field] = $constraint->message;
        }
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