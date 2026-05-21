<?php

declare(strict_types=1);

final class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function required(string $field, string $label): self
    {
        if ($this->value($field) === '') {
            $this->errors[$field][] = $label . ' is required.';
        }

        return $this;
    }

    public function email(string $field, string $label): self
    {
        $value = $this->value($field);

        if ($value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            $this->errors[$field][] = $label . ' must be a valid email address.';
        }

        return $this;
    }

    public function length(string $field, string $label, int $min, int $max): self
    {
        $length = mb_strlen($this->value($field));

        if ($length < $min || $length > $max) {
            $this->errors[$field][] = $label . ' must be between ' . $min . ' and ' . $max . ' characters.';
        }

        return $this;
    }

    public function password(string $field, string $label): self
    {
        $value = $this->value($field);

        if (
            $value !== ''
            && !preg_match('/^(?=.*[A-Za-z])(?=.*\d).{8,72}$/', $value)
        ) {
            $this->errors[$field][] = $label . ' must be 8-72 characters and include letters and numbers.';
        }

        return $this;
    }

    public function matches(string $field, string $otherField, string $label): self
    {
        if ($this->value($field) !== $this->value($otherField)) {
            $this->errors[$field][] = $label . ' does not match.';
        }

        return $this;
    }

    public function value(string $field): string
    {
        $value = $this->data[$field] ?? '';

        return is_string($value) ? trim($value) : '';
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }
}
